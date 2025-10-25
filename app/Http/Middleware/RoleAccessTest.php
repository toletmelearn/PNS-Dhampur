<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\NewRole;

class RoleAccessTest
{
    /**
     * Role access test middleware to verify proper permissions
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only run in test environment
        if (app()->environment('testing')) {
            $user = Auth::user();
            
            if ($user) {
                $role = $user->role;
                $route = $request->route()->getName();
                
                // Log access attempt for verification
                Log::channel('role_testing')->info('Access attempt', [
                    'user_id' => $user->id,
                    'role' => $role,
                    'route' => $route,
                    'method' => $request->method(),
                    'url' => $request->url(),
                    'timestamp' => now(),
                ]);
                
                // Verify access based on role
                $this->verifyRoleAccess($role, $route);
            }
        }
        
        return $next($request);
    }
    
    /**
     * Verify if the role should have access to the route
     *
     * @param string $role
     * @param string $route
     * @return bool
     */
    private function verifyRoleAccess($role, $route)
    {
        $accessMap = $this->getRoleAccessMap();
        
        // Check if role exists in access map
        if (!isset($accessMap[$role])) {
            Log::channel('role_testing')->warning('Unknown role', [
                'role' => $role,
                'route' => $route
            ]);
            return false;
        }
        
        // Check if route is explicitly defined
        if (isset($accessMap[$role]['routes'][$route])) {
            $hasAccess = $accessMap[$role]['routes'][$route];
            
            Log::channel('role_testing')->info('Access check', [
                'role' => $role,
                'route' => $route,
                'has_access' => $hasAccess,
                'explicitly_defined' => true
            ]);
            
            return $hasAccess;
        }
        
        // Check if route matches any patterns
        foreach ($accessMap[$role]['patterns'] as $pattern => $hasAccess) {
            if (preg_match($pattern, $route)) {
                Log::channel('role_testing')->info('Access check', [
                    'role' => $role,
                    'route' => $route,
                    'has_access' => $hasAccess,
                    'matched_pattern' => $pattern
                ]);
                
                return $hasAccess;
            }
        }
        
        // Default deny if not explicitly allowed
        Log::channel('role_testing')->info('Access check', [
            'role' => $role,
            'route' => $route,
            'has_access' => false,
            'reason' => 'no_matching_rule'
        ]);
        
        return false;
    }
    
    /**
     * Get the role access map defining permissions
     *
     * @return array
     */
    private function getRoleAccessMap()
    {
        return [
            NewRole::SUPER_ADMIN => [
                'description' => 'Full system access and configuration',
                'routes' => [
                    'admin.system.config' => true,
                    'admin.users.manage' => true,
                    'admin.schools.manage' => true,
                    'admin.finance.dashboard' => true,
                    'admin.reports.all' => true,
                ],
                'patterns' => [
                    '/^admin\./' => true,
                    '/^api\.admin\./' => true,
                ]
            ],
            NewRole::ADMIN => [
                'description' => 'School management and user creation',
                'routes' => [
                    'admin.system.config' => false,
                    'admin.users.manage' => true,
                    'admin.schools.manage' => true,
                    'admin.finance.dashboard' => true,
                    'admin.reports.school' => true,
                ],
                'patterns' => [
                    '/^admin\.(?!system\.)/' => true,
                    '/^api\.admin\.(?!system\.)/' => true,
                ]
            ],
            NewRole::PRINCIPAL => [
                'description' => 'Single school management',
                'routes' => [
                    'admin.system.config' => false,
                    'admin.users.manage' => true,
                    'admin.schools.manage' => true,
                    'admin.finance.dashboard' => false,
                    'admin.academic.dashboard' => true,
                    'admin.reports.school' => true,
                ],
                'patterns' => [
                    '/^admin\.academic\./' => true,
                    '/^admin\.users\./' => true,
                    '/^admin\.reports\.school/' => true,
                ]
            ],
            NewRole::TEACHER => [
                'description' => 'Class and subject management',
                'routes' => [
                    'teacher.classes.manage' => true,
                    'teacher.attendance.manage' => true,
                    'teacher.grades.manage' => true,
                    'teacher.syllabus.manage' => true,
                    'teacher.exams.manage' => true,
                ],
                'patterns' => [
                    '/^teacher\./' => true,
                    '/^api\.teacher\./' => true,
                ]
            ],
            NewRole::STUDENT => [
                'description' => 'Personal data access',
                'routes' => [
                    'student.profile' => true,
                    'student.syllabus.view' => true,
                    'student.results.view' => true,
                    'student.fees.pay' => true,
                ],
                'patterns' => [
                    '/^student\./' => true,
                    '/^api\.student\./' => true,
                ]
            ],
            NewRole::PARENT => [
                'description' => 'Child progress monitoring',
                'routes' => [
                    'parent.children.view' => true,
                    'parent.fees.pay' => true,
                    'parent.attendance.view' => true,
                    'parent.grades.view' => true,
                ],
                'patterns' => [
                    '/^parent\./' => true,
                    '/^api\.parent\./' => true,
                ]
            ],
        ];
    }
    
    /**
     * Generate a test report for all roles and routes
     *
     * @return array
     */
    public static function generateTestReport()
    {
        $instance = new self();
        $accessMap = $instance->getRoleAccessMap();
        $report = [];
        
        // Collect all unique routes
        $allRoutes = [];
        foreach ($accessMap as $role => $config) {
            foreach ($config['routes'] as $route => $access) {
                if (!in_array($route, $allRoutes)) {
                    $allRoutes[] = $route;
                }
            }
        }
        
        // Generate matrix of role access
        foreach ($allRoutes as $route) {
            $routeReport = [
                'route' => $route,
                'access' => []
            ];
            
            foreach ($accessMap as $role => $config) {
                $hasAccess = isset($config['routes'][$route]) ? $config['routes'][$route] : false;
                $routeReport['access'][$role] = $hasAccess;
            }
            
            $report[] = $routeReport;
        }
        
        return $report;
    }
}