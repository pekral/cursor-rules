.PHONY: help install test test-all test-coverage test-unit test-edge test-integration test-performance test-error test-mock clean

# Default target
help:
	@echo "Cursor Rules - Test Commands"
	@echo "============================"
	@echo ""
	@echo "Available commands:"
	@echo "  install        Install dependencies"
	@echo "  test          Run all tests"
	@echo "  test-all      Run all tests (alias for test)"
	@echo "  test-coverage Run tests with coverage report"
	@echo "  test-unit     Run unit tests only"
	@echo "  test-edge     Run edge case tests only"
	@echo "  test-integration Run integration tests only"
	@echo "  test-performance Run performance tests only"
	@echo "  test-error    Run error handling tests only"
	@echo "  test-mock     Run mock tests only"
	@echo "  clean         Clean up test artifacts"
	@echo "  help          Show this help message"

# Install dependencies
install:
	composer install

# Run all tests
test: test-all

# Run all tests
test-all:
	./vendor/bin/phpunit

# Run tests with coverage
test-coverage:
	./vendor/bin/phpunit --coverage-html coverage

# Run unit tests
test-unit:
	./vendor/bin/phpunit tests/InstallerTest.php

# Run edge case tests
test-edge:
	./vendor/bin/phpunit tests/InstallerEdgeCasesTest.php

# Run integration tests
test-integration:
	./vendor/bin/phpunit tests/InstallerIntegrationTest.php

# Run performance tests
test-performance:
	./vendor/bin/phpunit tests/InstallerPerformanceTest.php

# Run error handling tests
test-error:
	./vendor/bin/phpunit tests/InstallerErrorHandlingTest.php

# Run mock tests
test-mock:
	./vendor/bin/phpunit tests/InstallerMockTest.php

# Clean up test artifacts
clean:
	rm -rf .phpunit.cache/
	rm -rf coverage/
	rm -rf .phpunit.result.cache
	rm -rf tests/temp/
	rm -rf tests/output/
	find . -name "*.tmp" -delete
	find . -name "*.temp" -delete

# Quick test (fastest subset)
test-quick:
	./vendor/bin/phpunit tests/InstallerTest.php tests/InstallerMockTest.php

# Full test suite with verbose output
test-verbose:
	./vendor/bin/phpunit --verbose

# Test with stop on failure
test-strict:
	./vendor/bin/phpunit --stop-on-failure

# Test with memory limit check
test-memory:
	./vendor/bin/phpunit --process-isolation

# Install and run all tests
setup-and-test: install test

# Install and run tests with coverage
setup-and-coverage: install test-coverage
