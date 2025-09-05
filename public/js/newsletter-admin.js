/**
 * AI Newsletter Pro - Admin JavaScript
 */

(function ($) {
  "use strict";

  window.AINewsletterAdmin = {
    init: function () {
      this.setupFormHandlers();
      this.setupAjaxActions();
      this.setupWidgetBuilder();
      this.setupCampaignEditor();
      this.setupAnalyticsDashboard();
      this.setupIntegrationTesting();
    },

    /**
     * Setup form handlers
     */
    setupFormHandlers: function () {
      // Auto-save functionality
      let saveTimeout;
      $(
        ".ai-newsletter-admin input, .ai-newsletter-admin textarea, .ai-newsletter-admin select"
      ).on("change input", function () {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
          AINewsletterAdmin.autoSave();
        }, 2000);
      });

      // Settings form validation
      $("#ai-newsletter-settings-form").on("submit", function (e) {
        if (!AINewsletterAdmin.validateSettings()) {
          e.preventDefault();
          return false;
        }
      });

      // Import/Export handlers
      $("#ai-newsletter-import-btn").on("click", this.handleImport);
      $("#ai-newsletter-export-btn").on("click", this.handleExport);
    },

    /**
     * Setup AJAX actions
     */
    setupAjaxActions: function () {
      // Test email service connections
      $(".test-connection-btn").on("click", function () {
        const service = $(this).data("service");
        AINewsletterAdmin.testConnection(service, $(this));
      });

      // Bulk actions
      $("#bulk-action-selector").on("change", function () {
        const action = $(this).val();
        if (action) {
          AINewsletterAdmin.handleBulkAction(action);
        }
      });

      // Live preview
      $(".preview-btn").on("click", function () {
        const type = $(this).data("type");
        const id = $(this).data("id");
        AINewsletterAdmin.showPreview(type, id);
      });
    },

    /**
     * Setup widget builder
     */
    setupWidgetBuilder: function () {
      // Widget type selection
      $(".widget-type-selector").on("click", function () {
        const type = $(this).data("type");
        AINewsletterAdmin.loadWidgetBuilder(type);
      });

      // Color picker
      $(".color-picker").wpColorPicker({
        change: function (event, ui) {
          AINewsletterAdmin.updatePreview();
        },
      });

      // Widget settings updates
      $(".widget-setting").on(
        "change input",
        this.debounce(function () {
          AINewsletterAdmin.updatePreview();
        }, 300)
      );

      // Trigger settings
      $("#trigger-type").on("change", function () {
        AINewsletterAdmin.updateTriggerSettings($(this).val());
      });
    },

    /**
     * Setup campaign editor
     */
    setupCampaignEditor: function () {
      // Initialize rich text editor if available
      if (typeof wp !== "undefined" && wp.editor) {
        wp.editor.initialize("campaign-content", {
          tinymce: {
            wpautop: true,
            plugins: "lists,paste,tabfocus,fullscreen",
            toolbar1: "bold,italic,underline,link,unlink,undo,redo,fullscreen",
          },
          quicktags: true,
        });
      }

      // AI content generation
      $("#generate-ai-content").on("click", function () {
        AINewsletterAdmin.generateAIContent();
      });

      // Send test email
      $("#send-test-email").on("click", function () {
        const email = prompt("Enter email address for test:");
        if (email) {
          AINewsletterAdmin.sendTestEmail(email);
        }
      });

      // Schedule campaign
      $("#schedule-campaign").on("click", function () {
        AINewsletterAdmin.showScheduleDialog();
      });
    },

    /**
     * Setup analytics dashboard
     */
    setupAnalyticsDashboard: function () {
      // Chart initialization
      this.initializeCharts();

      // Date range picker
      $("#analytics-date-range").on("change", function () {
        const range = $(this).val();
        AINewsletterAdmin.updateAnalytics(range);
      });

      // Export analytics
      $("#export-analytics").on("click", function () {
        AINewsletterAdmin.exportAnalytics();
      });

      // Refresh data
      $("#refresh-analytics").on("click", function () {
        AINewsletterAdmin.refreshAnalytics();
      });
    },

    /**
     * Setup integration testing
     */
    setupIntegrationTesting: function () {
      // Service configuration
      $(".service-config").on("change", function () {
        const service = $(this).closest(".integration-card").data("service");
        AINewsletterAdmin.validateServiceConfig(service);
      });

      // Sync buttons
      $(".sync-subscribers-btn").on("click", function () {
        const service = $(this).data("service");
        AINewsletterAdmin.syncSubscribers(service);
      });
    },

    /**
     * Test email service connection
     */
    testConnection: function (service, $button) {
      const originalText = $button.text();
      $button.text("Testing...").prop("disabled", true);

      $.ajax({
        url: ai_newsletter_admin.ajax_url,
        type: "POST",
        data: {
          action: "ai_newsletter_test_connection",
          service: service,
          nonce: ai_newsletter_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            $button.removeClass("button-secondary").addClass("button-primary");
            AINewsletterAdmin.showNotice("Connection successful!", "success");
          } else {
            AINewsletterAdmin.showNotice(
              "Connection failed: " + response.data.message,
              "error"
            );
          }
        },
        error: function () {
          AINewsletterAdmin.showNotice("Connection test failed", "error");
        },
        complete: function () {
          $button.text(originalText).prop("disabled", false);
        },
      });
    },

    /**
     * Generate AI content
     */
    generateAIContent: function () {
      const $button = $("#generate-ai-content");
      const originalText = $button.text();

      $button.text("Generating...").prop("disabled", true);

      $.ajax({
        url: ai_newsletter_admin.ajax_url,
        type: "POST",
        data: {
          action: "ai_newsletter_generate_content",
          nonce: ai_newsletter_admin.nonce,
          criteria: $("#content-criteria").val(),
          tone: $("#content-tone").val(),
          length: $("#content-length").val(),
        },
        success: function (response) {
          if (response.success) {
            if (typeof wp !== "undefined" && wp.editor) {
              wp.editor.getContent("campaign-content", response.data.content);
            } else {
              $("#campaign-content").val(response.data.content);
            }
            AINewsletterAdmin.showNotice(
              "Content generated successfully!",
              "success"
            );
          } else {
            AINewsletterAdmin.showNotice(
              "Failed to generate content: " + response.data.message,
              "error"
            );
          }
        },
        error: function () {
          AINewsletterAdmin.showNotice("Content generation failed", "error");
        },
        complete: function () {
          $button.text(originalText).prop("disabled", false);
        },
      });
    },

    /**
     * Send test email
     */
    sendTestEmail: function (email) {
      $.ajax({
        url: ai_newsletter_admin.ajax_url,
        type: "POST",
        data: {
          action: "ai_newsletter_send_test",
          email: email,
          campaign_id: $("#campaign-id").val(),
          nonce: ai_newsletter_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            AINewsletterAdmin.showNotice(
              "Test email sent to " + email,
              "success"
            );
          } else {
            AINewsletterAdmin.showNotice("Failed to send test email", "error");
          }
        },
      });
    },

    /**
     * Initialize charts
     */
    initializeCharts: function () {
      // Subscriber growth chart
      if ($("#subscriber-chart").length && typeof Chart !== "undefined") {
        const ctx = $("#subscriber-chart")[0].getContext("2d");
        this.subscriberChart = new Chart(ctx, {
          type: "line",
          data: {
            labels: [],
            datasets: [
              {
                label: "Subscribers",
                data: [],
                borderColor: "#3b82f6",
                backgroundColor: "rgba(59, 130, 246, 0.1)",
                tension: 0.3,
              },
            ],
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: false,
              },
            },
            scales: {
              y: {
                beginAtZero: true,
              },
            },
          },
        });
      }

      // Widget performance chart
      if (
        $("#widget-performance-chart").length &&
        typeof Chart !== "undefined"
      ) {
        const ctx = $("#widget-performance-chart")[0].getContext("2d");
        this.widgetChart = new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: [],
            datasets: [
              {
                data: [],
                backgroundColor: [
                  "#3b82f6",
                  "#10b981",
                  "#f59e0b",
                  "#ef4444",
                  "#8b5cf6",
                ],
              },
            ],
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: "bottom",
              },
            },
          },
        });
      }
    },

    /**
     * Update analytics
     */
    updateAnalytics: function (range) {
      $.ajax({
        url: ai_newsletter_admin.ajax_url,
        type: "POST",
        data: {
          action: "ai_newsletter_get_analytics",
          range: range,
          nonce: ai_newsletter_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            AINewsletterAdmin.updateCharts(response.data);
            AINewsletterAdmin.updateStatCards(response.data);
          }
        },
      });
    },

    /**
     * Update charts with new data
     */
    updateCharts: function (data) {
      if (this.subscriberChart && data.subscriber_growth) {
        this.subscriberChart.data.labels = data.subscriber_growth.labels;
        this.subscriberChart.data.datasets[0].data =
          data.subscriber_growth.data;
        this.subscriberChart.update();
      }

      if (this.widgetChart && data.widget_performance) {
        this.widgetChart.data.labels = data.widget_performance.labels;
        this.widgetChart.data.datasets[0].data = data.widget_performance.data;
        this.widgetChart.update();
      }
    },

    /**
     * Update stat cards
     */
    updateStatCards: function (data) {
      if (data.stats) {
        Object.keys(data.stats).forEach(function (key) {
          $(`[data-stat="${key}"]`).text(data.stats[key]);
        });
      }
    },

    /**
     * Show admin notice
     */
    showNotice: function (message, type = "info") {
      const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

      $(".ai-newsletter-admin .wrap h1").after($notice);

      // Auto-dismiss after 5 seconds
      setTimeout(() => {
        $notice.fadeOut(() => $notice.remove());
      }, 5000);

      // Manual dismiss
      $notice.find(".notice-dismiss").on("click", function () {
        $notice.fadeOut(() => $notice.remove());
      });
    },

    /**
     * Auto-save functionality
     */
    autoSave: function () {
      const formData = $(".ai-newsletter-admin form").serialize();

      $.ajax({
        url: ai_newsletter_admin.ajax_url,
        type: "POST",
        data:
          formData +
          "&action=ai_newsletter_auto_save&nonce=" +
          ai_newsletter_admin.nonce,
        success: function (response) {
          if (response.success) {
            $(".auto-save-indicator")
              .text("Saved")
              .fadeIn()
              .delay(2000)
              .fadeOut();
          }
        },
      });
    },

    /**
     * Validate settings form
     */
    validateSettings: function () {
      let valid = true;

      // Check required fields
      $(".required").each(function () {
        if (!$(this).val()) {
          $(this).addClass("error");
          valid = false;
        } else {
          $(this).removeClass("error");
        }
      });

      // Validate email addresses
      $('input[type="email"]').each(function () {
        if ($(this).val() && !AINewsletterAdmin.isValidEmail($(this).val())) {
          $(this).addClass("error");
          valid = false;
        } else {
          $(this).removeClass("error");
        }
      });

      return valid;
    },

    /**
     * Email validation
     */
    isValidEmail: function (email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },

    /**
     * Debounce function
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
  };

  // Initialize when document is ready
  $(document).ready(function () {
    AINewsletterAdmin.init();
  });
})(jQuery);
