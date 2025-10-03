<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        
        $notifications = Notification::forUser($user->id)
            ->notExpired()
            ->when($request->type, function($query, $type) {
                return $query->byType($type);
            })
            ->when($request->priority, function($query, $priority) {
                return $query->byPriority($priority);
            })
            ->when($request->status === 'read', function($query) {
                return $query->read();
            })
            ->when($request->status === 'unread', function($query) {
                return $query->unread();
            })
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        if ($request->ajax()) {
            return response()->json([
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total()
                ]
            ]);
        }

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount()
    {
        $count = Notification::forUser(Auth::id())
            ->unread()
            ->notExpired()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get recent notifications for dropdown/popup
     */
    public function getRecent(Request $request)
    {
        $limit = $request->get('limit', 5);
        
        $notifications = Notification::forUser(Auth::id())
            ->notExpired()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = Notification::forUser(Auth::id())->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true, 'message' => 'Notification marked as read']);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread($id)
    {
        $notification = Notification::forUser(Auth::id())->findOrFail($id);
        $notification->markAsUnread();

        return response()->json(['success' => true, 'message' => 'Notification marked as unread']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Notification::forUser(Auth::id())
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $notification = Notification::forUser(Auth::id())->findOrFail($id);
        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted']);
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead()
    {
        Notification::forUser(Auth::id())
            ->read()
            ->delete();

        return response()->json(['success' => true, 'message' => 'All read notifications deleted']);
    }

    /**
     * Get notification statistics
     */
    public function getStats()
    {
        $userId = Auth::id();
        
        $stats = [
            'total' => Notification::forUser($userId)->count(),
            'unread' => Notification::forUser($userId)->unread()->count(),
            'read' => Notification::forUser($userId)->read()->count(),
            'by_type' => Notification::forUser($userId)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_priority' => Notification::forUser($userId)
                ->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority')
        ];

        return response()->json($stats);
    }

    /**
     * Create notification (for admin/teacher use)
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'priority' => 'in:low,normal,high,urgent',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:now'
        ]);

        $data = $request->only(['type', 'title', 'message', 'priority', 'scheduled_at', 'expires_at']);
        $data['sender_id'] = Auth::id();
        $data['sender_type'] = Auth::user()->role ?? 'admin';

        if (count($request->user_ids) === 1) {
            $notification = Notification::createForUser(
                $request->user_ids[0],
                $request->type,
                $request->title,
                $request->message,
                $request->data,
                $data
            );
        } else {
            Notification::createBulk(
                $request->user_ids,
                $request->type,
                $request->title,
                $request->message,
                $request->data,
                $data
            );
        }

        return response()->json(['success' => true, 'message' => 'Notification(s) created successfully']);
    }

    /**
     * Show notification details
     */
    public function show($id)
    {
        $notification = Notification::forUser(Auth::id())
            ->with('sender')
            ->findOrFail($id);

        // Mark as read when viewed
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json($notification);
    }

    /**
     * Get notification preferences (placeholder for future implementation)
     */
    public function getPreferences()
    {
        // This would typically fetch user notification preferences from a settings table
        $preferences = [
            'email_notifications' => true,
            'web_notifications' => true,
            'assignment_deadlines' => true,
            'assignment_grades' => true,
            'syllabus_updates' => true,
            'system_announcements' => true
        ];

        return response()->json($preferences);
    }

    /**
     * Update notification preferences (placeholder for future implementation)
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'web_notifications' => 'boolean',
            'assignment_deadlines' => 'boolean',
            'assignment_grades' => 'boolean',
            'syllabus_updates' => 'boolean',
            'system_announcements' => 'boolean'
        ]);

        // This would typically update user notification preferences in a settings table
        
        return response()->json(['success' => true, 'message' => 'Preferences updated successfully']);
    }
}
