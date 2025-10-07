<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\AcademicYear;
use App\Models\Holiday;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Traits\DateRangeValidationTrait;

class ConfigurationController extends Controller
{
    use DateRangeValidationTrait;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Configuration dashboard
     */
    public function index()
    {
        $stats = [
            'system_settings' => SystemSetting::count(),
            'academic_years' => AcademicYear::count(),
            'holidays' => Holiday::count(),
            'notification_templates' => NotificationTemplate::count(),
            'current_academic_year' => AcademicYear::current(),
            'active_holidays' => Holiday::active()->count(),
            'active_templates' => NotificationTemplate::active()->count()
        ];

        return view('admin.configuration.index', compact('stats'));
    }

    /**
     * System Settings
     */
    public function systemSettings()
    {
        $settings = SystemSetting::orderBy('category')->orderBy('sort_order')->get()->groupBy('category');
        
        return view('admin.configuration.system-settings', compact('settings'));
    }

    public function updateSystemSettings(Request $request)
    {
        $settings = $request->input('settings', []);
        
        foreach ($settings as $key => $value) {
            SystemSetting::set($key, $value);
        }
        
        Cache::forget('system_settings');
        
        return redirect()->back()->with('success', 'System settings updated successfully.');
    }

    /**
     * Academic Years
     */
    public function academicYears()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->paginate(10);
        $currentYear = AcademicYear::current();
        
        return view('admin.configuration.academic-years', compact('academicYears', 'currentYear'));
    }

    public function createAcademicYear()
    {
        return view('admin.configuration.academic-year-form');
    }

    public function storeAcademicYear(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:academic_years'],
            ...$this->getAcademicYearDateRangeValidationRules(),
            'description' => ['nullable', 'string']
        ], $this->getDateRangeValidationMessages());

        // Check for overlapping academic years
        $overlapping = AcademicYear::where(function ($query) use ($request) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($q) use ($request) {
                      $q->where('start_date', '<=', $request->start_date)
                        ->where('end_date', '>=', $request->end_date);
                  });
        })->exists();

        if ($overlapping) {
            return redirect()->back()->withErrors(['date_overlap' => 'The academic year dates overlap with an existing academic year.'])->withInput();
        }

        AcademicYear::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'is_active' => true,
            'is_current' => false,
            'settings' => []
        ]);

        return redirect()->route('admin.configuration.academic-years')->with('success', 'Academic year created successfully.');
    }

    public function editAcademicYear(AcademicYear $academicYear)
    {
        return view('admin.configuration.academic-year-form', compact('academicYear'));
    }

    public function updateAcademicYear(Request $request, AcademicYear $academicYear)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:academic_years,name,' . $academicYear->id],
            ...$this->getAcademicYearDateRangeValidationRules(),
            'description' => ['nullable', 'string']
        ], $this->getDateRangeValidationMessages());

        // Check for overlapping academic years (excluding current one)
        $overlapping = AcademicYear::where('id', '!=', $academicYear->id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                      });
            })->exists();

        if ($overlapping) {
            return redirect()->back()->withErrors(['date_overlap' => 'The academic year dates overlap with an existing academic year.'])->withInput();
        }

        $academicYear->update([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description
        ]);

        return redirect()->route('admin.configuration.academic-years')->with('success', 'Academic year updated successfully.');
    }

    public function setCurrentAcademicYear(AcademicYear $academicYear)
    {
        $academicYear->setCurrent();
        
        return redirect()->back()->with('success', 'Academic year set as current successfully.');
    }

    public function toggleAcademicYear(AcademicYear $academicYear)
    {
        $academicYear->update(['is_active' => !$academicYear->is_active]);
        
        return redirect()->back()->with('success', 'Academic year status updated successfully.');
    }

    /**
     * Holidays
     */
    public function holidays()
    {
        $holidays = Holiday::with('academicYear')->orderBy('start_date', 'desc')->paginate(15);
        $academicYears = AcademicYear::active()->get();
        
        return view('admin.configuration.holidays', compact('holidays', 'academicYears'));
    }

    public function createHoliday()
    {
        $academicYears = AcademicYear::active()->get();
        
        return view('admin.configuration.holiday-form', compact('academicYears'));
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            ...$this->getDateRangeValidationRules(),
            'type' => ['required', 'in:national,regional,school'],
            'description' => ['nullable', 'string']
        ], $this->getDateRangeValidationMessages());

        Holiday::create($request->all());

        return redirect()->route('admin.configuration.holidays')->with('success', 'Holiday created successfully.');
    }

    public function editHoliday(Holiday $holiday)
    {
        $academicYears = AcademicYear::active()->get();
        
        return view('admin.configuration.holiday-form', compact('holiday', 'academicYears'));
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:' . implode(',', array_keys(Holiday::TYPES)),
            'category' => 'required|in:' . implode(',', array_keys(Holiday::CATEGORIES)),
            'academic_year_id' => 'required|exists:academic_years,id',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|in:' . implode(',', array_keys(Holiday::RECURRENCE_PATTERNS))
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $holiday->update($request->all());

        return redirect()->route('admin.configuration.holidays')->with('success', 'Holiday updated successfully.');
    }

    public function toggleHoliday(Holiday $holiday)
    {
        $holiday->update(['is_active' => !$holiday->is_active]);
        
        return redirect()->back()->with('success', 'Holiday status updated successfully.');
    }

    public function deleteHoliday(Holiday $holiday)
    {
        $holiday->delete();
        
        return redirect()->back()->with('success', 'Holiday deleted successfully.');
    }

    /**
     * Notification Templates
     */
    public function notificationTemplates()
    {
        $templates = NotificationTemplate::orderBy('category')->orderBy('name')->paginate(15);
        
        return view('admin.configuration.notification-templates', compact('templates'));
    }

    public function createNotificationTemplate()
    {
        return view('admin.configuration.notification-template-form');
    }

    public function storeNotificationTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(NotificationTemplate::TYPES)),
            'category' => 'required|in:' . implode(',', array_keys(NotificationTemplate::CATEGORIES)),
            'subject' => 'required_if:type,email|nullable|string',
            'body' => 'required|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $template = NotificationTemplate::create([
            'name' => $request->name,
            'type' => $request->type,
            'category' => $request->category,
            'subject' => $request->subject,
            'body' => $request->body,
            'description' => $request->description,
            'variables' => $request->input('variables', []),
            'settings' => $request->input('settings', []),
            'is_active' => true,
            'is_system' => false
        ]);

        return redirect()->route('admin.configuration.notification-templates')->with('success', 'Notification template created successfully.');
    }

    public function editNotificationTemplate(NotificationTemplate $notificationTemplate)
    {
        if ($notificationTemplate->is_system) {
            return redirect()->back()->with('error', 'System templates cannot be edited.');
        }
        
        return view('admin.configuration.notification-template-form', compact('notificationTemplate'));
    }

    public function updateNotificationTemplate(Request $request, NotificationTemplate $notificationTemplate)
    {
        if ($notificationTemplate->is_system) {
            return redirect()->back()->with('error', 'System templates cannot be edited.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(NotificationTemplate::TYPES)),
            'category' => 'required|in:' . implode(',', array_keys(NotificationTemplate::CATEGORIES)),
            'subject' => 'required_if:type,email|nullable|string',
            'body' => 'required|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $notificationTemplate->update([
            'name' => $request->name,
            'type' => $request->type,
            'category' => $request->category,
            'subject' => $request->subject,
            'body' => $request->body,
            'description' => $request->description,
            'variables' => $request->input('variables', []),
            'settings' => $request->input('settings', [])
        ]);

        return redirect()->route('admin.configuration.notification-templates')->with('success', 'Notification template updated successfully.');
    }

    public function toggleNotificationTemplate(NotificationTemplate $notificationTemplate)
    {
        $notificationTemplate->update(['is_active' => !$notificationTemplate->is_active]);
        
        return redirect()->back()->with('success', 'Template status updated successfully.');
    }

    public function deleteNotificationTemplate(NotificationTemplate $notificationTemplate)
    {
        if ($notificationTemplate->is_system) {
            return redirect()->back()->with('error', 'System templates cannot be deleted.');
        }

        $notificationTemplate->delete();
        
        return redirect()->back()->with('success', 'Notification template deleted successfully.');
    }

    public function previewNotificationTemplate(NotificationTemplate $notificationTemplate)
    {
        $sampleVariables = [];
        
        // Generate sample data for preview
        foreach ($notificationTemplate->getAvailableVariables() as $variable) {
            switch ($variable) {
                case 'student_name':
                    $sampleVariables[$variable] = 'John Doe';
                    break;
                case 'school_name':
                    $sampleVariables[$variable] = 'PNS Dhampur';
                    break;
                case 'student_id':
                    $sampleVariables[$variable] = 'STU001';
                    break;
                case 'class_name':
                    $sampleVariables[$variable] = 'Class 10-A';
                    break;
                case 'date':
                    $sampleVariables[$variable] = Carbon::now()->format('Y-m-d');
                    break;
                case 'amount_due':
                    $sampleVariables[$variable] = '₹5,000';
                    break;
                case 'due_date':
                    $sampleVariables[$variable] = Carbon::now()->addDays(7)->format('Y-m-d');
                    break;
                default:
                    $sampleVariables[$variable] = '[Sample ' . ucfirst(str_replace('_', ' ', $variable)) . ']';
            }
        }
        
        $rendered = $notificationTemplate->render($sampleVariables);
        
        return response()->json([
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'variables' => $sampleVariables
        ]);
    }
}