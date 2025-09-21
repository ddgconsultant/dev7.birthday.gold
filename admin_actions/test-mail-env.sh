#!/bin/bash

# Test script for mail failure simulation using environment variables

case "$1" in
    enable)
        RATE="${2:-100}"
        export MAIL_TEST_FAILURE=1
        export MAIL_TEST_FAILURE_RATE=$RATE
        echo "✓ Test mode ENABLED with ${RATE}% failure rate"
        echo "Environment variables set:"
        echo "  MAIL_TEST_FAILURE=1"
        echo "  MAIL_TEST_FAILURE_RATE=$RATE"
        ;;

    disable)
        unset MAIL_TEST_FAILURE
        unset MAIL_TEST_FAILURE_RATE
        echo "✓ Test mode DISABLED"
        echo "Environment variables cleared"
        ;;

    status)
        echo "Current Environment Variables:"
        if [ -n "$MAIL_TEST_FAILURE" ]; then
            echo "  MAIL_TEST_FAILURE=$MAIL_TEST_FAILURE (ENABLED)"
        else
            echo "  MAIL_TEST_FAILURE not set (DISABLED)"
        fi

        if [ -n "$MAIL_TEST_FAILURE_RATE" ]; then
            echo "  MAIL_TEST_FAILURE_RATE=$MAIL_TEST_FAILURE_RATE%"
        else
            echo "  MAIL_TEST_FAILURE_RATE not set (default: 100%)"
        fi
        ;;

    test)
        echo "Testing mail with current settings..."
        echo "Visit: https://dev7.birthday.gold/admin_actions/test-mail-failure.php"
        echo "Or test contact form: https://dev7.birthday.gold/contact"
        ;;

    *)
        echo "Mail Failure Test Environment Setup"
        echo ""
        echo "Usage: $0 {enable|disable|status|test} [rate]"
        echo ""
        echo "Commands:"
        echo "  enable [rate]  - Enable test mode with optional failure rate (0-100)"
        echo "  disable        - Disable test mode"
        echo "  status         - Show current environment variables"
        echo "  test           - Show URLs for testing"
        echo ""
        echo "Examples:"
        echo "  $0 enable 50   # Enable with 50% failure rate"
        echo "  $0 status      # Check current settings"
        echo "  $0 disable     # Disable test mode"
        ;;
esac