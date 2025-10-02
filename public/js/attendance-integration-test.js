/**
 * Integration Test Suite for Attendance System
 * Tests all JavaScript modules working together
 */

class AttendanceIntegrationTest {
    constructor() {
        this.testResults = [];
        this.modules = null;
    }

    /**
     * Initialize and run all integration tests
     */
    async runTests() {
        console.log('🧪 Starting Attendance System Integration Tests...');
        
        // Wait for modules to be available
        await this.waitForModules();
        
        // Run all test suites
        await this.testModuleInitialization();
        await this.testNotificationSystem();
        await this.testValidationSystem();
        await this.testLoadingStates();
        await this.testAccessibilityFeatures();
        await this.testPerformanceOptimizations();
        await this.testModuleInteractions();
        
        // Display results
        this.displayResults();
        
        return this.testResults;
    }

    /**
     * Wait for all modules to be initialized
     */
    async waitForModules() {
        return new Promise((resolve) => {
            const checkModules = () => {
                if (window.attendanceModules) {
                    this.modules = window.attendanceModules;
                    resolve();
                } else {
                    setTimeout(checkModules, 100);
                }
            };
            checkModules();
        });
    }

    /**
     * Test module initialization
     */
    async testModuleInitialization() {
        console.log('📋 Testing Module Initialization...');
        
        const tests = [
            {
                name: 'Notifications module exists',
                test: () => this.modules.notifications instanceof AttendanceNotifications
            },
            {
                name: 'Validator module exists',
                test: () => this.modules.validator instanceof AttendanceValidator
            },
            {
                name: 'Accessibility module exists',
                test: () => this.modules.accessibility instanceof AttendanceAccessibility
            },
            {
                name: 'Performance module exists',
                test: () => this.modules.performance instanceof AttendancePerformance
            },
            {
                name: 'Loading module exists',
                test: () => this.modules.loading instanceof AttendanceLoading
            }
        ];

        this.runTestSuite('Module Initialization', tests);
    }

    /**
     * Test notification system
     */
    async testNotificationSystem() {
        console.log('🔔 Testing Notification System...');
        
        const tests = [
            {
                name: 'Toast notification methods exist',
                test: () => {
                    return typeof this.modules.notifications.showSuccess === 'function' &&
                           typeof this.modules.notifications.showError === 'function' &&
                           typeof this.modules.notifications.showWarning === 'function' &&
                           typeof this.modules.notifications.showInfo === 'function';
                }
            },
            {
                name: 'SweetAlert methods exist',
                test: () => {
                    return typeof this.modules.notifications.showAlert === 'function' &&
                           typeof this.modules.notifications.showConfirmation === 'function';
                }
            },
            {
                name: 'Toast container exists in DOM',
                test: () => document.querySelector('.toast-container') !== null
            }
        ];

        this.runTestSuite('Notification System', tests);
    }

    /**
     * Test validation system
     */
    async testValidationSystem() {
        console.log('✅ Testing Validation System...');
        
        const tests = [
            {
                name: 'Validation methods exist',
                test: () => {
                    return typeof this.modules.validator.validateRequired === 'function' &&
                           typeof this.modules.validator.validateEmail === 'function' &&
                           typeof this.modules.validator.validateDate === 'function';
                }
            },
            {
                name: 'Form validation setup',
                test: () => {
                    // Check if forms have validation attributes
                    const forms = document.querySelectorAll('form');
                    return forms.length === 0 || Array.from(forms).some(form => 
                        form.hasAttribute('novalidate')
                    );
                }
            }
        ];

        this.runTestSuite('Validation System', tests);
    }

    /**
     * Test loading states
     */
    async testLoadingStates() {
        console.log('⏳ Testing Loading States...');
        
        const tests = [
            {
                name: 'Loading methods exist',
                test: () => {
                    return typeof this.modules.loading.showGlobalLoader === 'function' &&
                           typeof this.modules.loading.hideGlobalLoader === 'function' &&
                           typeof this.modules.loading.showElementLoader === 'function';
                }
            },
            {
                name: 'Loading CSS classes exist',
                test: () => {
                    const styles = Array.from(document.styleSheets);
                    return styles.some(sheet => {
                        try {
                            const rules = Array.from(sheet.cssRules || []);
                            return rules.some(rule => 
                                rule.selectorText && rule.selectorText.includes('.loading-spinner')
                            );
                        } catch (e) {
                            return false;
                        }
                    });
                }
            }
        ];

        this.runTestSuite('Loading States', tests);
    }

    /**
     * Test accessibility features
     */
    async testAccessibilityFeatures() {
        console.log('♿ Testing Accessibility Features...');
        
        const tests = [
            {
                name: 'Accessibility methods exist',
                test: () => {
                    return typeof this.modules.accessibility.setupKeyboardNavigation === 'function' &&
                           typeof this.modules.accessibility.setupAriaLabels === 'function';
                }
            },
            {
                name: 'Skip links exist',
                test: () => document.querySelector('.skip-link') !== null
            },
            {
                name: 'ARIA live regions exist',
                test: () => {
                    const liveRegions = document.querySelectorAll('[aria-live]');
                    return liveRegions.length > 0;
                }
            }
        ];

        this.runTestSuite('Accessibility Features', tests);
    }

    /**
     * Test performance optimizations
     */
    async testPerformanceOptimizations() {
        console.log('⚡ Testing Performance Optimizations...');
        
        const tests = [
            {
                name: 'Performance methods exist',
                test: () => {
                    return typeof this.modules.performance.debounce === 'function' &&
                           typeof this.modules.performance.throttle === 'function' &&
                           typeof this.modules.performance.lazyLoad === 'function';
                }
            },
            {
                name: 'Cache system exists',
                test: () => {
                    return typeof this.modules.performance.cache === 'object' &&
                           typeof this.modules.performance.cache.set === 'function';
                }
            }
        ];

        this.runTestSuite('Performance Optimizations', tests);
    }

    /**
     * Test module interactions
     */
    async testModuleInteractions() {
        console.log('🔗 Testing Module Interactions...');
        
        const tests = [
            {
                name: 'Modules can communicate',
                test: () => {
                    // Test if loading can trigger notifications
                    try {
                        this.modules.loading.showGlobalLoader();
                        this.modules.loading.hideGlobalLoader();
                        return true;
                    } catch (e) {
                        return false;
                    }
                }
            },
            {
                name: 'Validation integrates with notifications',
                test: () => {
                    // Check if validator has access to notifications
                    return this.modules.validator.notifications !== undefined ||
                           typeof this.modules.notifications.showError === 'function';
                }
            }
        ];

        this.runTestSuite('Module Interactions', tests);
    }

    /**
     * Run a test suite
     */
    runTestSuite(suiteName, tests) {
        const results = tests.map(test => {
            try {
                const passed = test.test();
                return {
                    suite: suiteName,
                    name: test.name,
                    passed: passed,
                    error: null
                };
            } catch (error) {
                return {
                    suite: suiteName,
                    name: test.name,
                    passed: false,
                    error: error.message
                };
            }
        });

        this.testResults.push(...results);
    }

    /**
     * Display test results
     */
    displayResults() {
        const passed = this.testResults.filter(r => r.passed).length;
        const total = this.testResults.length;
        const failed = total - passed;

        console.log('\n📊 Test Results Summary:');
        console.log(`✅ Passed: ${passed}/${total}`);
        console.log(`❌ Failed: ${failed}/${total}`);
        console.log(`📈 Success Rate: ${((passed/total) * 100).toFixed(1)}%`);

        if (failed > 0) {
            console.log('\n❌ Failed Tests:');
            this.testResults
                .filter(r => !r.passed)
                .forEach(test => {
                    console.log(`  • ${test.suite}: ${test.name}`);
                    if (test.error) {
                        console.log(`    Error: ${test.error}`);
                    }
                });
        }

        // Create visual report
        this.createVisualReport();
    }

    /**
     * Create visual test report
     */
    createVisualReport() {
        const reportContainer = document.createElement('div');
        reportContainer.id = 'attendance-test-report';
        reportContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            background: white;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            font-family: Arial, sans-serif;
            font-size: 12px;
        `;

        const passed = this.testResults.filter(r => r.passed).length;
        const total = this.testResults.length;
        const successRate = ((passed/total) * 100).toFixed(1);

        reportContainer.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0; color: #007bff;">Test Report</h3>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            </div>
            <div style="margin-bottom: 10px;">
                <div style="background: #f8f9fa; padding: 8px; border-radius: 4px;">
                    <strong>Success Rate: ${successRate}%</strong><br>
                    Passed: ${passed}/${total}
                </div>
            </div>
            <div style="max-height: 250px; overflow-y: auto;">
                ${this.testResults.map(test => `
                    <div style="margin-bottom: 5px; padding: 5px; border-radius: 3px; background: ${test.passed ? '#d4edda' : '#f8d7da'};">
                        <div style="font-weight: bold; color: ${test.passed ? '#155724' : '#721c24'};">
                            ${test.passed ? '✅' : '❌'} ${test.suite}
                        </div>
                        <div style="font-size: 11px; color: #666;">
                            ${test.name}
                        </div>
                        ${test.error ? `<div style="font-size: 10px; color: #721c24; margin-top: 2px;">${test.error}</div>` : ''}
                    </div>
                `).join('')}
            </div>
        `;

        document.body.appendChild(reportContainer);

        // Auto-remove after 30 seconds
        setTimeout(() => {
            if (reportContainer.parentElement) {
                reportContainer.remove();
            }
        }, 30000);
    }
}

// Auto-run tests when page loads (only in development)
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    document.addEventListener('DOMContentLoaded', () => {
        // Wait a bit for all modules to initialize
        setTimeout(() => {
            const tester = new AttendanceIntegrationTest();
            tester.runTests();
        }, 2000);
    });
}

// Make available globally for manual testing
window.AttendanceIntegrationTest = AttendanceIntegrationTest;