/**
 * AI Newsletter Pro - Frontend JavaScript
 */

(function ($) {
  "use strict";

  // Main frontend object
  window.AINewsletterFrontend = {
    /**
     * Initialize frontend functionality
     */
    init: function () {
      this.setupScrollTracking();
      this.setupEngagementTracking();
      this.setupFormEnhancements();
      this.handleStatusMessages();
      this.setupAccessibility();
    },

    /**
     * Setup scroll tracking for analytics
     */
    setupScrollTracking: function () {
      let scrollDepth = 0;
      let scrollMarkers = [25, 50, 75, 100];
      let trackedMarkers = [];

      $(window).on(
        "scroll",
        this.throttle(function () {
          const winHeight = $(window).height();
          const docHeight = $(document).height();
          const scrollTop = $(window).scrollTop();
          const trackLength = docHeight - winHeight;
          const pctScrolled = Math.floor((scrollTop / trackLength) * 100);

          if (pctScrolled > scrollDepth) {
            scrollDepth = pctScrolled;
          }

          // Track milestone markers
          scrollMarkers.forEach(function (marker) {
            if (
              pctScrolled >= marker &&
              trackedMarkers.indexOf(marker) === -1
            ) {
              trackedMarkers.push(marker);
              AINewsletterFrontend.trackEvent("scroll_depth", {
                depth: marker,
                page: window.location.pathname,
              });
            }
          });
        }, 250)
      );
    },

    /**
     * Setup engagement tracking
     */
    setupEngagementTracking: function () {
      let startTime = Date.now();
      let engaged = false;

      // Track page engagement
      $(document).on("mousemove keypress scroll touchstart", function () {
        if (!engaged) {
          engaged = true;
          AINewsletterFrontend.trackEvent("page_engagement", {
            timeToEngage: Date.now() - startTime,
          });
        }
      });

      // Track time on page
      $(window).on("beforeunload", function () {
        const timeOnPage = Date.now() - startTime;
        if (timeOnPage > 10000) {
          // More than 10 seconds
          AINewsletterFrontend.trackEvent("time_on_page", {
            duration: timeOnPage,
            engaged: engaged,
          });
        }
      });

      // Track outbound links
      $('a[href^="http"]')
        .not('[href*="' + window.location.hostname + '"]')
        .on("click", function () {
          AINewsletterFrontend.trackEvent("outbound_link", {
            url: $(this).attr("href"),
            text: $(this).text().substring(0, 100),
          });
        });
    },

    /**
     * Enhance newsletter forms
     */
    setupFormEnhancements: function () {
      // Email validation enhancement
      $('input[type="email"]').on("blur", function () {
        const email = $(this).val();
        const $field = $(this);

        if (email && !AINewsletterFrontend.isValidEmail(email)) {
          $field.addClass("invalid-email");
          AINewsletterFrontend.showFieldError(
            $field,
            "Please enter a valid email address"
          );
        } else {
          $field.removeClass("invalid-email");
          AINewsletterFrontend.hideFieldError($field);
        }
      });

      // Real-time email suggestions
      $('input[type="email"]').on(
        "input",
        this.debounce(function () {
          const email = $(this).val();
          const $field = $(this);

          if (email.includes("@") && !email.includes(".")) {
            AINewsletterFrontend.showEmailSuggestion($field, email);
          }
        }, 300)
      );

      // Form submission tracking
      $(".ai-newsletter-form").on("submit", function () {
        AINewsletterFrontend.trackEvent("form_submission_attempt", {
          form_id: $(this).data("widget-id") || "unknown",
          source: $(this).find('input[name="source"]').val() || "unknown",
        });
      });
    },

    /**
     * Handle status messages from URL parameters
     */
    handleStatusMessages: function () {
      const urlParams = new URLSearchParams(window.location.search);
      const status = urlParams.get("newsletter_status");

      if (status) {
        this.showStatusNotification(status);

        // Clean URL after showing message
        if (window.history && window.history.replaceState) {
          const cleanUrl = window.location.href.split("?")[0];
          window.history.replaceState({}, document.title, cleanUrl);
        }
      }
    },

    /**
     * Setup accessibility enhancements
     */
    setupAccessibility: function () {
      // Keyboard navigation for custom elements
      $(".ai-newsletter-close").on("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          $(this).click();
        }
      });

      // Focus management for popups
      $(document).on("ai-newsletter-popup-opened", function (e, popupId) {
        const $popup = $("#" + popupId);
        const $focusable = $popup.find(
          'input, button, [tabindex]:not([tabindex="-1"])'
        );

        if ($focusable.length) {
          $focusable.first().focus();
        }
      });

      // Screen reader announcements
      this.setupScreenReaderAnnouncements();
    },

    /**
     * Setup screen reader announcements
     */
    setupScreenReaderAnnouncements: function () {
      // Create announcement region
      if (!$("#ai-newsletter-announcements").length) {
        $("body").append(
          '<div id="ai-newsletter-announcements" aria-live="polite" aria-atomic="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;"></div>'
        );
      }
    },

    /**
     * Announce message to screen readers
     */
    announceToScreenReader: function (message) {
      $("#ai-newsletter-announcements").text(message);
    },

    /**
     * Show status notification
     */
    showStatusNotification: function (status) {
      const messages = {
        subscribed: "Thank you for subscribing to our newsletter!",
        confirmed: "Your subscription has been confirmed!",
        unsubscribed: "You have been unsubscribed from our newsletter.",
        error: "An error occurred. Please try again.",
      };

      const message = messages[status];
      if (!message) return;

      const notificationClass = status === "error" ? "error" : "success";
      const $notification = $(`
                <div class="ai-newsletter-status-notification ${notificationClass}" role="alert">
                    <div class="ai-newsletter-notification-content">
                        <span class="ai-newsletter-notification-message">${message}</span>
                        <button class="ai-newsletter-notification-close" aria-label="Close notification">&times;</button>
                    </div>
                </div>
            `);

      // Add styles
      $notification.css({
        position: "fixed",
        top: "20px",
        right: "20px",
        maxWidth: "400px",
        padding: "16px",
        borderRadius: "8px",
        backgroundColor: status === "error" ? "#fef2f2" : "#ecfdf5",
        border: status === "error" ? "1px solid #ef4444" : "1px solid #10b981",
        color: status === "error" ? "#dc2626" : "#059669",
        boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1)",
        zIndex: 999999,
        opacity: 0,
        transform: "translateY(-20px)",
      });

      $("body").append($notification);

      // Animate in
      $notification.animate(
        {
          opacity: 1,
          transform: "translateY(0)",
        },
        300
      );

      // Announce to screen readers
      this.announceToScreenReader(message);

      // Auto-dismiss after 5 seconds
      setTimeout(() => {
        this.hideStatusNotification($notification);
      }, 5000);

      // Manual dismiss
      $notification
        .find(".ai-newsletter-notification-close")
        .on("click", () => {
          this.hideStatusNotification($notification);
        });
    },

    /**
     * Hide status notification
     */
    hideStatusNotification: function ($notification) {
      $notification.animate(
        {
          opacity: 0,
          transform: "translateY(-20px)",
        },
        300,
        function () {
          $(this).remove();
        }
      );
    },

    /**
     * Show email suggestion
     */
    showEmailSuggestion: function ($field, email) {
      const commonDomains = [
        "gmail.com",
        "yahoo.com",
        "hotmail.com",
        "outlook.com",
      ];
      const emailParts = email.split("@");

      if (emailParts.length === 2) {
        const username = emailParts[0];
        const domain = emailParts[1];

        // Simple domain suggestion
        const suggestion = commonDomains.find((d) => d.startsWith(domain));

        if (suggestion && suggestion !== domain) {
          const suggestedEmail = username + "@" + suggestion;
          this.showFieldSuggestion($field, suggestedEmail);
        }
      }
    },

    /**
     * Show field suggestion
     */
    showFieldSuggestion: function ($field, suggestion) {
      $(".ai-newsletter-suggestion").remove();

      const $suggestion = $(`
                <div class="ai-newsletter-suggestion">
                    Did you mean <button type="button" class="suggestion-link">${suggestion}</button>?
                </div>
            `);

      $suggestion.css({
        fontSize: "12px",
        color: "#6b7280",
        marginTop: "4px",
      });

      $suggestion.find(".suggestion-link").css({
        background: "none",
        border: "none",
        color: "#3b82f6",
        cursor: "pointer",
        textDecoration: "underline",
      });

      $field.after($suggestion);

      $suggestion.find(".suggestion-link").on("click", function () {
        $field.val(suggestion);
        $suggestion.remove();
        $field.focus();
      });
    },

    /**
     * Show field error
     */
    showFieldError: function ($field, message) {
      this.hideFieldError($field);

      const $error = $(
        `<div class="ai-newsletter-field-error">${message}</div>`
      );
      $error.css({
        color: "#dc2626",
        fontSize: "12px",
        marginTop: "4px",
      });

      $field.after($error);
    },

    /**
     * Hide field error
     */
    hideFieldError: function ($field) {
      $field.next(".ai-newsletter-field-error").remove();
    },

    /**
     * Track custom event
     */
    trackEvent: function (eventName, data) {
      // Send to analytics if available
      if (typeof gtag !== "undefined") {
        gtag("event", eventName, {
          custom_parameter: JSON.stringify(data),
        });
      }

      // Send to plugin analytics
      if (typeof ai_newsletter_pro_ajax !== "undefined") {
        $.post(ai_newsletter_pro_ajax.ajax_url, {
          action: "ai_newsletter_track_event",
          event: eventName,
          data: data,
          nonce: ai_newsletter_pro_ajax.nonce,
        });
      }

      // Console log for debugging
      if (window.location.search.includes("debug=1")) {
        console.log("AI Newsletter Event:", eventName, data);
      }
    },

    /**
     * Validate email address
     */
    isValidEmail: function (email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },

    /**
     * Throttle function calls
     */
    throttle: function (func, limit) {
      let inThrottle;
      return function () {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
          func.apply(context, args);
          inThrottle = true;
          setTimeout(() => (inThrottle = false), limit);
        }
      };
    },

    /**
     * Debounce function calls
     */
    debounce: function (func, wait, immediate) {
      let timeout;
      return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function () {
          timeout = null;
          if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
      };
    },

    /**
     * Get device information
     */
    getDeviceInfo: function () {
      return {
        isMobile:
          /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
            navigator.userAgent
          ),
        isTablet:
          /iPad|Android/i.test(navigator.userAgent) &&
          !/Mobile/i.test(navigator.userAgent),
        screenWidth: window.screen.width,
        screenHeight: window.screen.height,
        viewportWidth: window.innerWidth,
        viewportHeight: window.innerHeight,
      };
    },

    /**
     * Handle newsletter widget interactions
     */
    setupWidgetInteractions: function () {
      // Track widget visibility
      if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                const widgetId = entry.target.id;
                this.trackEvent("widget_visible", {
                  widget_id: widgetId,
                  visibility_ratio: entry.intersectionRatio,
                });
              }
            });
          },
          {
            threshold: [0.1, 0.5, 0.9],
          }
        );

        $(
          ".ai-newsletter-inline, .ai-newsletter-floating, .ai-newsletter-banner"
        ).each(function () {
          observer.observe(this);
        });
      }

      // Track widget interactions
      $(".ai-newsletter-form input, .ai-newsletter-form button").on(
        "focus",
        function () {
          const widgetId = $(this)
            .closest(".ai-newsletter-form")
            .data("widget-id");
          this.trackEvent("widget_interaction", {
            widget_id: widgetId,
            element: this.tagName.toLowerCase(),
          });
        }
      );
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    AINewsletterFrontend.init();
    AINewsletterFrontend.setupWidgetInteractions();
  });

  // Progressive Web App support
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", function () {
      // Register service worker if available
      const swPath = ai_newsletter_pro_ajax?.sw_path;
      if (swPath) {
        navigator.serviceWorker
          .register(swPath)
          .then(function (registration) {
            console.log("AI Newsletter SW registered");
          })
          .catch(function (error) {
            console.log("AI Newsletter SW registration failed");
          });
      }
    });
  }
})(jQuery);
