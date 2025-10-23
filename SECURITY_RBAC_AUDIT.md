# Security and RBAC Architecture Audit

Date: 2025-10-16

## Scope
- Middleware registration and aliasing in `app/Http/Kernel.php`
- Route-level middleware use in `routes/web.php`, `routes/api.php`, and `routes/auth.php`
- RBAC models (`User`, `NewUser`, `Role`, `NewRole`) and permission services
- Overlaps across `RoleMiddleware`, `PermissionMiddleware`, `ModuleMiddleware`, `AttendanceSecurityMiddleware`, `RoleBasedAccess`, and `PermissionCheck`

## Middleware Inventory
- Global middleware (Kernel `$middleware`):
  - `ProductionSecurityMiddleware`, `SecurityHeaders`, `SecurityHeadersMiddleware`, `AuditMiddleware`, `FilePermissionMiddleware`, `UserActivityMiddleware`, etc.
- Web group (Kernel `$middlewareGroups['web']`):
  - `SecurityValidationMiddleware`, `RateLimitingMiddleware`, `EnhancedInputSanitization`, `SanitizeInput`, `EnhancedXssProtectionMiddleware`, `SecurityHeadersMiddleware`, CSRF/session stack.
- Route middleware aliases (Kernel `$routeMiddleware`):
  - Auth/session: `auth`, `verified`, `auth.session`, `session.security` (SecureSessionConfig), `password.confirm`
  - RBAC: `role` → `RoleMiddleware`, `permission` → `PermissionMiddleware`
  - Module/security: `attendance.security`, `module`, `security`, `audit`, various `rate.limit` variants

## Route Usage Snapshot
- `routes/api.php`:
  - Uses `role` and `permission` aliases extensively; attendance endpoints add `attendance.security`.
- `routes/web.php`:
  - Mix of `can:` policies and controller middleware; generally clean.
- `routes/auth.php`:
  - Direct class references: `RoleBasedAccess::class`, `PermissionCheck::class`, `SessionSecurity::class` (not alias-based).

## Key Findings
1. Duplicate RBAC middleware paths
   - Aliases: `role` → `RoleMiddleware`, `permission` → `PermissionMiddleware` registered in Kernel.
   - Direct class usage in `routes/auth.php`: `RoleBasedAccess` and `PermissionCheck` implement similar checks (auth/account status, hierarchy, permission logging), causing divergence in enforcement logic.

2. Session security duplication/mismatch
   - Kernel alias: `session.security` → `SecureSessionConfig`.
   - Routes use `SessionSecurity::class` directly in `auth.php`, which is separate from the alias (different class names and likely differing logic).

3. Mixed role/permission sources
   - `RoleMiddleware` relies on `$user->role`, `Role::getRoleLevel()`, and `canAccessAttendance()` (legacy Role model patterns).
   - Controllers reference `NewRole::SUPER_ADMIN` and `NewUser` relationships; `ModuleMiddleware` uses `$user->roles->pluck('name')` and `hasAnyRole()` (new model patterns).
   - `PermissionService` / `NewUser` support role-assignments and direct permissions while `User.php` delegates to legacy `Role` model. This split risks inconsistent results depending on which middleware/controller path is executed.

4. Overlapping security responsibilities
   - `AttendanceSecurityMiddleware` performs auth, rate limiting, attendance access, CSRF checks, and role escalation detection. Portions overlap with `RoleMiddleware` and global/web security middleware (CSRF, rate limit).
   - `PermissionMiddleware` and `PermissionCheck` both validate authentication, account status, and permissions, and both log security events.

5. Operational risk
   - Fragmented enforcement increases chances of bypass or inconsistent authorization when routes use class-based middlewares vs aliases.
   - Harder to maintain and audit: business rules live in multiple places with different naming and subtle differences.

## Recommendations
1. Standardize on Kernel aliases for RBAC
   - Use `role` and `permission` aliases everywhere; avoid direct references to `RoleBasedAccess` and `PermissionCheck` in routes.
   - If `RoleBasedAccess` and `PermissionCheck` contain unique logic you need, consolidate that logic into `RoleMiddleware` and `PermissionMiddleware` respectively via shared services.

2. Unify session security
   - Replace `SessionSecurity::class` route usage with `session.security` alias (mapped to `SecureSessionConfig`).
   - Ensure `RoleBasedSessionTimeout` focuses only on timeout policy; avoid duplicating headers or CSRF concerns covered elsewhere.

3. Converge models to the new RBAC system
   - Prefer `NewUser`/`NewRole` across middlewares and controllers.
   - Update `RoleMiddleware` to use `NewUser` role assignments (e.g., `hasAnyRole`, `getHighestRoleLevel`) rather than `$user->role` strings and legacy `Role::getRoleLevel()`.
   - Provide a thin adapter for legacy code paths if necessary to minimize refactor blast radius.

4. Centralize authorization rules
   - Introduce an `AccessControlService` that encapsulates: role hierarchy, admin override restrictions, module access mapping, and contextual permission checks (teacher/class, parent/student).
   - Have `RoleMiddleware`, `PermissionMiddleware`, and `ModuleMiddleware` delegate to this service for consistent decisions.

5. Reduce overlap in attendance security
   - Keep rate limiting and CSRF concerns in global/web middleware or Laravel defaults when possible.
   - Restrict `AttendanceSecurityMiddleware` to attendance-specific checks (module access, role escalation, domain-specific validations) and delegate role/permission checks to the standardized aliases.

6. Logging and monitoring
   - Consolidate security logging via a single channel/service (e.g., `SecurityAuditService`), ensuring consistent context across all middlewares.

## Quick Wins (Low Risk)
- Replace direct class middlewares in `routes/auth.php`:
  - `RoleBasedAccess::class:...` → `role:...`
  - `PermissionCheck::class:...` → `permission:...`
  - `SessionSecurity::class` → `session.security`
- Document the convention: routes must use Kernel aliases; direct class references are prohibited.

## Suggested Implementation Plan
- Phase 1: Route cleanup and alias standardization
  - Replace direct middleware classes in `routes/auth.php` with aliases; verify no behavior regressions.
- Phase 2: Middleware consolidation
  - Migrate unique logic from `RoleBasedAccess`/`PermissionCheck` into `RoleMiddleware`/`PermissionMiddleware` via `AccessControlService`.
- Phase 3: Model convergence
  - Update `RoleMiddleware` and helpers to use `NewUser`/`NewRole` consistently.
  - Deprecate legacy `User`/`Role` paths where feasible.

## Notable Files Reviewed
- `app/Http/Kernel.php` (middleware registrations and aliases)
- `routes/api.php`, `routes/web.php`, `routes/auth.php` (middleware usage)
- `app/Http/Middleware/RoleMiddleware.php`, `PermissionMiddleware.php`, `ModuleMiddleware.php`, `AttendanceSecurityMiddleware.php`
- `app/Http/Middleware/SecureSessionConfig.php` (via alias), `RoleBasedSessionTimeout.php`
- `app/Models/User.php`, `NewUser.php`, `Role.php`, `NewRole.php`
- `app/Services/PermissionService.php`

## Risk Summary
- Medium risk of inconsistent authorization decisions due to multiple RBAC implementations and direct class middleware usage.
- Maintainability risk due to duplicated logic and scattered security responsibilities.

## Next Steps
- Approve Phase 1 changes (route alias standardization) and schedule Phase 2–3 refactors.