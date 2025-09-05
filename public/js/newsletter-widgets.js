/**
 * AI Newsletter Pro - Widget JavaScript
 */

(function ($) {
  "use strict";

  // Widget controller object
  const AINewsletterWidgets = {
    init: function () {
      this.setupPopupTriggers();
      this.setupFormHandlers();
      this.setupCloseHandlers();
      this.trackImpressions();
      this.setupScrollTriggers();
      this.setupExitIntentTriggers();
    },

    // Setup popup triggers
    setupPopupTriggers: function () {
      $(".ai-newsletter-popup-overlay").each(function () {
        const $popup = $(this);
        const trigger = $popup.data("trigger");
        const triggerValue = $popup.data("trigger-value");

        switch (trigger) {
          case "time":
            setTimeout(() => {
              AINewsletterWidgets.showPopup($popup);
            }, triggerValue);
            break;

          case "scroll":
            AINewsletterWidgets.setupScrollTrigger($popup, triggerValue);
            break;

          case "exit":
            AINewsletterWidgets.setupExitIntent($popup);
            break;

          case "immediate":
            AINewsletterWidgets.showPopup($popup);
            break;
        }
      });
    },

    // Setup scroll triggers
    setupScrollTriggers: function () {
      let scrollTriggered = false;

      $(window).on("scroll", function () {
        if (scrollTriggered) return;

        $('.ai-newsletter-popup-overlay[data-trigger="scroll"]').each(
          function () {
            const $popup = $(this);
            const triggerValue = $popup.data("trigger-value") || 50;
            const scrollPercent =
              ($(window).scrollTop() /
                ($(document).height() - $(window).height())) *
              100;

            if (scrollPercent >= triggerValue) {
              AINewsletterWidgets.showPopup($popup);
              scrollTriggered = true;
            }
          }
        );
      });
    },

    // Setup exit intent triggers
    setupExitIntentTriggers: function () {
      let exitTriggered = false;

      $(document).on("mouseleave", function (e) {
        if (exitTriggered || e.clientY > 0) return;

        $('.ai-newsletter-popup-overlay[data-trigger="exit"]').each(
          function () {
            AINewsletterWidgets.showPopup($(this));
            exitTriggered = true;
          }
        );
      });
    },

    // Show popup
    showPopup: function ($popup) {
      // Check if popup was already shown or dismissed
      const popupId = $popup.attr("id");
      if (
        localStorage.getItem("ai_newsletter_dismissed_" + popupId) ||
        localStorage.getItem("ai_newsletter_subscribed_" + popupId)
      ) {
        return;
      }

      $popup.fadeIn(300);
      $("body").addClass("ai-newsletter-popup-open");

      // Track impression
      this.trackWidgetImpression($popup.attr("id"));
    },

    // Setup form handlers
    setupFormHandlers: function () {
      $(document).on("submit", ".ai-newsletter-form", function (e) {
        e.preventDefault();

        const $form = $(this);
        const $email = $form.find('input[type="email"]');
        const email = $email.val().trim();
        const widgetId = $form.data("widget-id");

        // Validate email
        if (!AINewsletterWidgets.isValidEmail(email)) {
          AINewsletterWidgets.showMessage(
            $form,
            "error",
            ai_newsletter_pro_ajax.messages.invalid_email
          );
          return;
        }

        // Set loading state
        $form.addClass("loading");

        // Submit subscription
        AINewsletterWidgets.submitSubscription(email, widgetId, $form);
      });
    },

    // Setup close handlers
    setupCloseHandlers: function () {
      // Close popup
      window.aiNewsletterClosePopup = function (popupId) {
        const $popup = $("#" + popupId);
        $popup.fadeOut(300);
        $("body").removeClass("ai-newsletter-popup-open");
        localStorage.setItem("ai_newsletter_dismissed_" + popupId, Date.now());
      };

      // Close floating widget
      window.aiNewsletterCloseFloating = function (widgetId) {
        $("#" + widgetId).fadeOut(300);
        localStorage.setItem("ai_newsletter_dismissed_" + widgetId, Date.now());
      };

      // Close banner
      window.aiNewsletterCloseBanner = function (bannerId) {
        $("#" + bannerId).slideUp(300);
        localStorage.setItem("ai_newsletter_dismissed_" + bannerId, Date.now());
      };

      // Close on overlay click
      $(document).on("click", ".ai-newsletter-popup-overlay", function (e) {
        if (e.target === this) {
          const popupId = $(this).attr("id");
          aiNewsletterClosePopup(popupId);
        }
      });

      // Close on escape key
      $(document).on("keydown", function (e) {
        if (e.keyCode === 27) {
          // ESC key
          $(".ai-newsletter-popup-overlay:visible").each(function () {
            const popupId = $(this).attr("id");
            aiNewsletterClosePopup(popupId);
          });
        }
      });
    },

    // Submit subscription
    submitSubscription: function (email, widgetId, $form) {
      $.ajax({
        url: ai_newsletter_pro_ajax.ajax_url,
        type: "POST",
        data: {
          action: "ai_newsletter_subscribe",
          email: email,
          source: widgetId,
          nonce: ai_newsletter_pro_ajax.nonce,
        },
        success: function (response) {
          $form.removeClass("loading");

          if (response.success) {
            AINewsletterWidgets.showMessage(
              $form,
              "success",
              ai_newsletter_pro_ajax.messages.success
            );
            $form.find('input[type="email"]').val("");

            // Track conversion
            AINewsletterWidgets.trackConversion(widgetId);

            // Set subscribed flag
            localStorage.setItem(
              "ai_newsletter_subscribed_" + widgetId,
              Date.now()
            );

            // Auto-close popup after success
            setTimeout(() => {
              if ($form.closest(".ai-newsletter-popup-overlay").length) {
                const popupId = $form
                  .closest(".ai-newsletter-popup-overlay")
                  .attr("id");
                aiNewsletterClosePopup(popupId);
              }
            }, 2000);
          } else {
            const message =
              response.data && response.data.message
                ? response.data.message
                : ai_newsletter_pro_ajax.messages.error;
            AINewsletterWidgets.showMessage($form, "error", message);
          }
        },
        error: function () {
          $form.removeClass("loading");
          AINewsletterWidgets.showMessage(
            $form,
            "error",
            ai_newsletter_pro_ajax.messages.error
          );
        },
      });
    },

    // Show message
    showMessage: function ($form, type, message) {
      // Remove existing messages
      $form.find(".ai-newsletter-message").remove();

      // Create new message
      const $message = $(
        '<div class="ai-newsletter-message ' + type + '">' + message + "</div>"
      );
      $form.append($message);

      // Auto-hide error messages
      if (type === "error") {
        setTimeout(() => {
          $message.fadeOut(300, function () {
            $(this).remove();
          });
        }, 5000);
      }
    },

    // Track widget impression
    trackWidgetImpression: function (widgetId) {
      const numericId = widgetId.replace("ai-newsletter-widget-", "");

      $.ajax({
        url: ai_newsletter_pro_ajax.ajax_url,
        type: "POST",
        data: {
          action: "ai_newsletter_widget_impression",
          widget_id: numericId,
          nonce: ai_newsletter_pro_ajax.nonce,
        },
      });
    },

    // Track conversions
    trackConversion: function (widgetId) {
      const numericId = widgetId.replace("ai-newsletter-widget-", "");

      $.ajax({
        url: ai_newsletter_pro_ajax.ajax_url,
        type: "POST",
        data: {
          action: "ai_newsletter_widget_conversion",
          widget_id: numericId,
          nonce: ai_newsletter_pro_ajax.nonce,
        },
      });
    },

    // Track impressions for visible widgets
    trackImpressions: function () {
      // Track floating widgets
      $(".ai-newsletter-floating").each(function () {
        const widgetId = $(this).attr("id");
        if (!localStorage.getItem("ai_newsletter_dismissed_" + widgetId)) {
          AINewsletterWidgets.trackWidgetImpression(widgetId);
        }
      });

      // Track banner widgets
      $(".ai-newsletter-banner").each(function () {
        const widgetId = $(this).attr("id");
        if (!localStorage.getItem("ai_newsletter_dismissed_" + widgetId)) {
          AINewsletterWidgets.trackWidgetImpression(widgetId);
        }
      });

      // Track inline widgets when they come into view
      if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                const widgetId = $(entry.target).attr("id");
                AINewsletterWidgets.trackWidgetImpression(widgetId);
                observer.unobserve(entry.target);
              }
            });
          },
          { threshold: 0.5 }
        );

        $(".ai-newsletter-inline").each(function () {
          observer.observe(this);
        });
      }
    },

    // Email validation
    isValidEmail: function (email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },

    // Setup scroll trigger for specific popup
    setupScrollTrigger: function ($popup, triggerValue) {
      let triggered = false;

      $(window).on("scroll", function () {
        if (triggered) return;

        const scrollPercent =
          ($(window).scrollTop() /
            ($(document).height() - $(window).height())) *
          100;

        if (scrollPercent >= triggerValue) {
          AINewsletterWidgets.showPopup($popup);
          triggered = true;
        }
      });
    },

    // Setup exit intent for specific popup
    setupExitIntent: function ($popup) {
      let triggered = false;

      $(document).on("mouseleave", function (e) {
        if (triggered || e.clientY > 0) return;

        AINewsletterWidgets.showPopup($popup);
        triggered = true;
      });
    },
  };

  // Cookie/localStorage utilities
  const AINewsletterStorage = {
    set: function (key, value, days = 30) {
      const expires = new Date();
      expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
      localStorage.setItem(
        key,
        JSON.stringify({
          value: value,
          expires: expires.getTime(),
        })
      );
    },

    get: function (key) {
      const item = localStorage.getItem(key);
      if (!item) return null;

      try {
        const parsed = JSON.parse(item);
        if (new Date().getTime() > parsed.expires) {
          localStorage.removeItem(key);
          return null;
        }
        return parsed.value;
      } catch (e) {
        return null;
      }
    },

    remove: function (key) {
      localStorage.removeItem(key);
    },
  };

  // A/B Testing utilities
  const AINewsletterABTest = {
    getVariant: function (testName, variants = ["A", "B"]) {
      const key = "ai_newsletter_ab_" + testName;
      let variant = AINewsletterStorage.get(key);

      if (!variant) {
        variant = variants[Math.floor(Math.random() * variants.length)];
        AINewsletterStorage.set(key, variant, 30);
      }

      return variant;
    },

    trackEvent: function (testName, variant, event) {
      $.ajax({
        url: ai_newsletter_pro_ajax.ajax_url,
        type: "POST",
        data: {
          action: "ai_newsletter_ab_track",
          test_name: testName,
          variant: variant,
          event: event,
          nonce: ai_newsletter_pro_ajax.nonce,
        },
      });
    },
  };

  // Performance optimization
  const AINewsletterPerf = {
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
  };

  // Initialize when DOM is ready
  $(document).ready(function () {
    AINewsletterWidgets.init();

    // Hide dismissed widgets
    $(".ai-newsletter-floating, .ai-newsletter-banner").each(function () {
      const widgetId = $(this).attr("id");
      if (
        localStorage.getItem("ai_newsletter_dismissed_" + widgetId) ||
        localStorage.getItem("ai_newsletter_subscribed_" + widgetId)
      ) {
        $(this).hide();
      }
    });
  });

  // Expose utilities globally
  window.AINewsletterWidgets = AINewsletterWidgets;
  window.AINewsletterStorage = AINewsletterStorage;
  window.AINewsletterABTest = AINewsletterABTest;
})(jQuery);
