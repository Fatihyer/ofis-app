<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  @include('layouts.partials.head')
  

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  @yield('style')

<style>
.global-toast-container {
    position: fixed;
    top: 72px;
    right: 18px;
    z-index: 2060;
    width: min(420px, calc(100vw - 28px));
    display: grid;
    gap: 10px;
}
.global-hermes-toast {
    border: 1px solid #f59e0b;
    border-left: 5px solid #d97706;
    border-radius: 8px;
    background: #fffbeb;
    color: #78350f;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .16);
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 700;
}
.global-hermes-toast strong {
    display: block;
    color: #92400e;
    margin-bottom: 2px;
}
.global-hermes-toast .close,
.global-twilio-toast .close,
.global-client-details-toast .close,
.global-user-notification-toast .close {
    line-height: 1;
    opacity: .65;
}
.global-twilio-toast {
    border: 1px solid #ef4444;
    border-left: 5px solid #dc2626;
    border-radius: 8px;
    background: #fef2f2;
    color: #7f1d1d;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .16);
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 700;
}
.global-twilio-toast strong {
    display: block;
    color: #991b1b;
    margin-bottom: 2px;
}
.global-client-details-toast {
    border: 1px solid #f59e0b;
    border-left: 5px solid #f59e0b;
    border-radius: 8px;
    background: #fff7ed;
    color: #7c2d12;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .16);
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 700;
}
.global-user-notification-toast {
    border: 1px solid #60a5fa;
    border-left: 5px solid #2563eb;
    border-radius: 8px;
    background: #eff6ff;
    color: #1e3a8a;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .16);
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 750;
}
.global-user-notification-toast strong {
    display: block;
    color: #1d4ed8;
    margin-bottom: 2px;
}
.global-toast-heading {
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
}
.global-toast-context {
    color: #7c2d12;
    font-size: 12px;
    font-weight: 800;
}
.global-toast-context-parts {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 4px;
}
.global-toast-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 3px 7px;
    font-size: 11px;
    font-weight: 850;
    line-height: 1.15;
}
.global-toast-chip.agency { background: #e0f2fe; color: #075985; border: 1px solid #7dd3fc; }
.global-toast-chip.file-user { background: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; }
.global-toast-chip.operation { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
.global-toast-issues {
    display: grid;
    gap: 5px;
    margin-top: 7px;
}
.global-toast-issue {
    border-radius: 7px;
    padding: 6px 8px;
    font-weight: 900;
    line-height: 1.25;
}
.global-toast-issue.client { background: #ffedd5; color: #9a3412; border: 1px solid #fdba74; }
.global-toast-issue.mission { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.global-toast-issue.enroute { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
.global-toast-issue.refused { background: #fecaca; color: #7f1d1d; border: 1px solid #f87171; }
.global-toast-issue.modified { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.global-toast-transfer-line {
    margin-top: 6px;
    color: #334155;
    font-size: 12px;
    font-weight: 800;
}
.global-client-details-toast strong {
    display: block;
    color: #9a3412;
    margin-bottom: 2px;
}
.global-toast-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    color: #1d4ed8;
    font-weight: 800;
    text-decoration: underline;
}
.global-toast-link .operation-label {
    color: #166534;
    font-weight: 900;
    text-decoration: none;
}
@media (max-width: 767.98px) {
    .global-toast-container {
        top: 62px;
        right: 10px;
        left: 10px;
        width: auto;
    }
}
</style>
</head>
<body>

@if (!Auth::guest())
  @include('layouts.partials.nav')
@endif


@if (!Auth::guest())
  @php $showClientDriverDetailsToasts = !Auth::user()->hasRole('Superadmin'); @endphp
  <div class="global-toast-container" id="globalHermesToastContainer" aria-live="polite" aria-label="Alertes Hermes" data-endpoint="{{ route('hermes.engine_alerts') }}" data-user-notifications-endpoint="{{ route('alerts.user_notifications') }}" data-user-notifications-read-url="{{ url('/alerts/user-notifications') }}" @if($showClientDriverDetailsToasts) data-client-details-endpoint="{{ route('alerts.client_driver_details') }}" @endif data-refresh-ms="{{ max(1, (int) ($globalHermesToastRefreshMinutes ?? 5)) * 60000 }}">
      @foreach (($globalHermesToastAlerts ?? []) as $alert)
          @php
              $alertId = is_array($alert) ? ($alert['id'] ?? md5($alert['message'] ?? '')) : md5((string) $alert);
              $alertMessage = is_array($alert) ? ($alert['message'] ?? '') : (string) $alert;
          @endphp
          <div class="global-hermes-toast alert-dismissible fade show" role="alert" data-alert-id="{{ $alertId }}">
              <button type="button" class="close global-toast-close" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
              <strong>Hermes</strong>
              <div class="global-hermes-toast-message">{{ $alertMessage }}</div>
          </div>
      @endforeach

      @if($showClientDriverDetailsToasts)
      @foreach (($globalClientDriverDetailsToastAlerts ?? []) as $alert)
          @php
              $alertId = is_array($alert) ? ($alert['id'] ?? md5($alert['message'] ?? '')) : md5((string) $alert);
              $alertMessage = is_array($alert) ? ($alert['message'] ?? '') : (string) $alert;
              $alertUrl = is_array($alert) ? ($alert['url'] ?? null) : null;
              $alertTransferId = is_array($alert) ? ($alert['transfer_id'] ?? null) : null;
              $alertContext = is_array($alert) ? ($alert['context'] ?? null) : null;
              $alertContextParts = is_array($alert) ? ($alert['context_parts'] ?? []) : [];
              $alertIssues = is_array($alert) ? ($alert['issues'] ?? []) : [];
              $alertScheduleText = is_array($alert) ? ($alert['schedule_text'] ?? null) : null;
              $alertTransferContext = is_array($alert) ? ($alert['transfer_context'] ?? null) : null;
          @endphp
          <div class="global-client-details-toast alert-dismissible fade show" role="alert" data-alert-id="{{ $alertId }}">
              <button type="button" class="close global-toast-close" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
              <div class="global-toast-heading">
                  <strong>Attention</strong>
                  @if ($alertContext && empty($alertContextParts))
                      <span class="global-toast-context">{{ $alertContext }}</span>
                  @endif
              </div>
              @if(!empty($alertContextParts))
                  <div class="global-toast-context-parts">
                      @if(!empty($alertContextParts['agency']))
                          <span class="global-toast-chip agency">{{ $alertContextParts['agency'] }}</span>
                      @endif
                      @if(!empty($alertContextParts['file_user']))
                          <span class="global-toast-chip file-user">{{ $alertContextParts['file_user'] }}</span>
                      @endif
                      @if(!empty($alertContextParts['operation']))
                          <span class="global-toast-chip operation">Opération: {{ $alertContextParts['operation'] }}</span>
                      @endif
                  </div>
              @endif
              @if(!empty($alertIssues))
                  <div class="global-toast-issues">
                      @foreach($alertIssues as $issue)
                          @php
                              $issueClass = str_contains($issue, 'Détails chauffeur') ? 'client'
                                  : (str_contains($issue, 'Mission non confirmée') ? 'mission'
                                  : (str_contains($issue, 'En route') ? 'enroute'
                                  : (str_contains($issue, 'refusé') ? 'refused'
                                  : (str_contains($issue, 'reconfirmer') ? 'modified' : ''))));
                          @endphp
                          <div class="global-toast-issue {{ $issueClass }}">{{ $issue }}</div>
                      @endforeach
                  </div>
                  <div class="global-toast-transfer-line">{{ $alertScheduleText }} {{ $alertTransferContext }}</div>
              @else
                  <div class="global-client-details-toast-message">{{ $alertMessage }}</div>
              @endif
              @if ($alertUrl)
                  <a class="global-toast-link" href="{{ $alertUrl }}">
                      Ouvrir le transfert #{{ $alertTransferId ?: '' }}
                      @if(!empty($alertContextParts['operation']))
                          <span class="operation-label">Opération: {{ $alertContextParts['operation'] }}</span>
                      @endif
                  </a>
              @endif
          </div>
      @endforeach
      @endif

      @foreach (($globalTwilioToastMessages ?? []) as $message)
          <div class="global-twilio-toast alert-dismissible fade show" role="alert">
              <button type="button" class="close global-toast-close" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
              <strong>Twilio</strong>
              <div>{{ $message }}</div>
          </div>
      @endforeach
  </div>
@endif

@if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<!-- SAYFA GENELİ FLEX -->
<div class="d-flex" style="min-height: 100vh;">

  <!-- Sidebar (sadece ikonlar) -->
  <div class="d-none d-md-flex flex-column align-items-center bg-light border-end sidebar-icon" style="width: 50px;">
      @include('layouts.partials.sidebar')
  </div>

  <!-- İçerik alanı -->
  <div class="flex-grow-1 p-3 content-area">
      @if(Session::has('flash_message'))
          <div class="alert alert-success"><em> {!! session('flash_message') !!}</em></div>
      @endif

      @include('errors.list')
      @yield('content')
  </div>
</div>

<!-- Footer -->
@include('layouts.partials.footer')

<!-- JS -->
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- jQuery UI (sortable için) -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
   
@vite(['resources/js/app.js'])



<script>
  function deleteNotification() {
    if(!confirm('Tüm bildirimleri silmek istediğinize emin misiniz?')) return;

    $.ajax({
        url: "{{ route('notifications.delete') }}",
        type: "DELETE",
        headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
        success: function() {
            location.reload();
        },
        error: function() {
            alert("Silme işlemi sırasında hata oluştu.");
        }
    });
}

  function yazdir() {
      window.print();
  }

  var globalToastAutoHideMs = 30000;
  var hermesDismissStorageKey = 'hermesToastDismissedAlertsV2';
  var hermesDismissLegacyStorageKey = 'hermesToastDismissedAlerts';

  try {
      localStorage.removeItem(hermesDismissLegacyStorageKey);
  } catch (e) {}

  function hideGlobalToast(toast) {
      $(toast).fadeOut(180, function () {
          $(this).remove();
      });
  }

  function scheduleGlobalToastAutoHide(toast) {
      var existingTimer = toast.data('auto-hide-timer');
      if (existingTimer) {
          clearTimeout(existingTimer);
      }

      var timer = setTimeout(function () {
          if (toast.closest('body').length && !toast.data('is-hovered')) {
              hideGlobalToast(toast);
          }
      }, globalToastAutoHideMs);

      toast.data('auto-hide-timer', timer);
  }

  function autoHideGlobalToasts(scope) {
      $(scope || document).find('.global-hermes-toast, .global-twilio-toast, .global-client-details-toast, .global-user-notification-toast').each(function () {
          scheduleGlobalToastAutoHide($(this));
      });
  }

  $(document)
      .on('mouseenter', '.global-hermes-toast, .global-twilio-toast, .global-client-details-toast, .global-user-notification-toast', function () {
          var toast = $(this);
          toast.data('is-hovered', true);
          var existingTimer = toast.data('auto-hide-timer');
          if (existingTimer) {
              clearTimeout(existingTimer);
          }
      })
      .on('mouseleave', '.global-hermes-toast, .global-twilio-toast, .global-client-details-toast, .global-user-notification-toast', function () {
          var toast = $(this);
          toast.data('is-hovered', false);
          scheduleGlobalToastAutoHide(toast);
      });

  if (window.toastr) {
      toastr.options = Object.assign({}, toastr.options || {}, {
          timeOut: globalToastAutoHideMs,
          extendedTimeOut: 10000,
          closeButton: true,
          progressBar: true,
          newestOnTop: true,
          preventDuplicates: true
      });
  }

  function getDismissedHermesAlerts() {
      try {
          return JSON.parse(localStorage.getItem(hermesDismissStorageKey) || '{}');
      } catch (e) {
          return {};
      }
  }

  function setDismissedHermesAlert(alertId) {
      if (!alertId) return;
      var dismissed = getDismissedHermesAlerts();
      dismissed[alertId] = Date.now();
      localStorage.setItem(hermesDismissStorageKey, JSON.stringify(dismissed));
  }

  function normalizeHermesAlerts(alertsOrMessages) {
      return (alertsOrMessages || []).map(function (alert) {
          if (typeof alert === 'string') {
              return { id: alert, message: alert };
          }
          return { id: alert.id || alert.message, message: alert.message || '' };
      }).filter(function (alert) {
          return alert.id && alert.message;
      });
  }

  function visibleHermesAlerts(alertsOrMessages) {
      var dismissed = getDismissedHermesAlerts();
      return normalizeHermesAlerts(alertsOrMessages).filter(function (alert) {
          return !dismissed[alert.id];
      });
  }

  function renderHermesToasts(alertsOrMessages) {
      var container = $('#globalHermesToastContainer');
      if (!container.length) return;

      var alerts = visibleHermesAlerts(alertsOrMessages);

      if (alerts.length === 0) {
          container.find('.global-hermes-toast').each(function () {
              hideGlobalToast(this);
          });
          return;
      }

      var current = container.find('.global-hermes-toast').map(function () {
          return $(this).data('alert-id') + ':' + $(this).find('.global-hermes-toast-message').text();
      }).get();
      var next = alerts.map(function (alert) {
          return alert.id + ':' + alert.message;
      });

      if (JSON.stringify(current) === JSON.stringify(next)) return;

      container.find('.global-hermes-toast').remove();
      alerts.forEach(function (alert) {
          container.append(
              '<div class="global-hermes-toast alert-dismissible fade show" role="alert" data-alert-id="' + $('<div>').text(alert.id).html() + '">' +
                  '<button type="button" class="close global-toast-close" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>' +
                  '<strong>Hermes</strong>' +
                  '<div class="global-hermes-toast-message"></div>' +
              '</div>'
          );
          container.find('.global-hermes-toast-message').last().text(alert.message);
      });

      autoHideGlobalToasts(container);
  }

  function refreshHermesToasts() {
      var container = $('#globalHermesToastContainer');
      if (!container.length) return;
      var endpoint = container.data('endpoint');
      if (!endpoint) return;

      $.get(endpoint, function (response) {
          renderHermesToasts(response.alerts || response.messages || []);
      });
  }

  function normalizeClientDetailsAlerts(alertsOrMessages) {
      return (alertsOrMessages || []).map(function (alert) {
          if (typeof alert === 'string') {
              return { id: alert, message: alert };
          }
          return {
              id: alert.id || alert.message,
              message: alert.message || '',
              url: alert.url || '',
              transfer_id: alert.transfer_id || '',
              context: alert.context || '',
              context_parts: alert.context_parts || {},
              issues: alert.issues || [],
              schedule_text: alert.schedule_text || '',
              transfer_context: alert.transfer_context || ''
          };
      }).filter(function (alert) {
          return alert.id && alert.message;
      });
  }

  function clientDetailsIssueClass(issue) {
      if ((issue || '').indexOf('Détails chauffeur') !== -1) return 'client';
      if ((issue || '').indexOf('Mission non confirmée') !== -1) return 'mission';
      if ((issue || '').indexOf('En route') !== -1) return 'enroute';
      if ((issue || '').indexOf('refusé') !== -1) return 'refused';
      if ((issue || '').indexOf('reconfirmer') !== -1) return 'modified';
      return '';
  }

  function renderClientDetailsToasts(alertsOrMessages) {
      var container = $('#globalHermesToastContainer');
      if (!container.length) return;

      var alerts = normalizeClientDetailsAlerts(alertsOrMessages);

      container.find('.global-client-details-toast').remove();

      alerts.forEach(function (alert) {
          container.append(
              '<div class="global-client-details-toast alert-dismissible fade show" role="alert" data-alert-id="' + $('<div>').text(alert.id).html() + '">' +
                  '<button type="button" class="close global-toast-close" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>' +
                  '<div class="global-toast-heading"><strong>Attention</strong></div>' +
                  '<div class="global-client-details-toast-message"></div>' +
              '</div>'
          );
          var toast = container.find('.global-client-details-toast').last();
          if (alert.context && (!alert.context_parts || Object.keys(alert.context_parts).length === 0)) {
              var context = document.createElement('span');
              context.className = 'global-toast-context';
              context.textContent = alert.context;
              toast.find('.global-toast-heading').append(context);
          }
          if (alert.context_parts && Object.keys(alert.context_parts).length > 0) {
              var contextParts = document.createElement('div');
              contextParts.className = 'global-toast-context-parts';

              if (alert.context_parts.agency) {
                  var agency = document.createElement('span');
                  agency.className = 'global-toast-chip agency';
                  agency.textContent = alert.context_parts.agency;
                  contextParts.appendChild(agency);
              }
              if (alert.context_parts.file_user) {
                  var fileUser = document.createElement('span');
                  fileUser.className = 'global-toast-chip file-user';
                  fileUser.textContent = alert.context_parts.file_user;
                  contextParts.appendChild(fileUser);
              }
              if (alert.context_parts.operation) {
                  var operation = document.createElement('span');
                  operation.className = 'global-toast-chip operation';
                  operation.textContent = 'Opération: ' + alert.context_parts.operation;
                  contextParts.appendChild(operation);
              }

              toast.append(contextParts);
          }

          if (alert.issues && alert.issues.length) {
              var issues = document.createElement('div');
              issues.className = 'global-toast-issues';
              alert.issues.forEach(function (issue) {
                  var issueNode = document.createElement('div');
                  issueNode.className = 'global-toast-issue ' + clientDetailsIssueClass(issue);
                  issueNode.textContent = issue;
                  issues.appendChild(issueNode);
              });
              toast.append(issues);

              var transferLine = document.createElement('div');
              transferLine.className = 'global-toast-transfer-line';
              transferLine.textContent = [alert.schedule_text, alert.transfer_context].filter(Boolean).join(' ');
              toast.append(transferLine);
          } else {
              toast.find('.global-client-details-toast-message').text(alert.message);
          }
          if (alert.url) {
              var link = document.createElement('a');
              link.className = 'global-toast-link';
              link.href = alert.url;
              link.textContent = 'Ouvrir le transfert #' + (alert.transfer_id || '');
              if (alert.context_parts && alert.context_parts.operation) {
                  var operationLabel = document.createElement('span');
                  operationLabel.className = 'operation-label';
                  operationLabel.textContent = 'Opération: ' + alert.context_parts.operation;
                  link.appendChild(operationLabel);
              }
              toast.append(link);
          }
      });

      autoHideGlobalToasts(container);
  }

  function refreshClientDetailsToasts() {
      var container = $('#globalHermesToastContainer');
      if (!container.length) return;
      var endpoint = container.data('client-details-endpoint');
      if (!endpoint) return;

      $.get(endpoint, function (response) {
          renderClientDetailsToasts(response.alerts || response.messages || []);
      });
  }

  function renderUserNotificationToasts(alerts) {
      var container = $('#globalHermesToastContainer');
      if (!container.length) return;

      container.find('.global-user-notification-toast').remove();

      (alerts || []).forEach(function (alert) {
          var toast = $(
              '<div class="global-user-notification-toast alert-dismissible fade show" role="alert" data-notification-id="' + $('<div>').text(alert.id || '').html() + '">' +
                  '<button type="button" class="close global-toast-close" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>' +
                  '<strong></strong>' +
                  '<div class="global-user-notification-message"></div>' +
                  '<div class="global-toast-context-parts"></div>' +
                  '<a class="global-toast-link" href="#"></a>' +
              '</div>'
          );

          toast.find('strong').text(alert.title || 'Notification');
          toast.find('.global-user-notification-message').text(alert.message || '');

          var parts = toast.find('.global-toast-context-parts');
          if (alert.customer_name) {
              $('<span class="global-toast-chip agency"></span>').text(alert.customer_name).appendTo(parts);
          }
          $('<span class="global-toast-chip operation"></span>').text('Prix: ' + (alert.new_price_text || '-')).appendTo(parts);
          if (alert.updated_by) {
              $('<span class="global-toast-chip file-user"></span>').text('Donné par: ' + alert.updated_by).appendTo(parts);
          }

          if (alert.url) {
              toast.find('.global-toast-link').attr('href', alert.url).text('Ouvrir la demande #' + (alert.talep_id || ''));
          } else {
              toast.find('.global-toast-link').remove();
          }

          container.append(toast);
      });

      autoHideGlobalToasts(container);
  }

  function refreshUserNotificationToasts() {
      var container = $('#globalHermesToastContainer');
      if (!container.length) return;
      var endpoint = container.data('user-notifications-endpoint');
      if (!endpoint) return;

      $.get(endpoint, function (response) {
          renderUserNotificationToasts(response.alerts || []);
      });
  }

  function markUserNotificationRead(notificationId) {
      if (!notificationId) return;
      var container = $('#globalHermesToastContainer');
      var baseUrl = container.data('user-notifications-read-url');
      if (!baseUrl) return;

      $.post(baseUrl + '/' + encodeURIComponent(notificationId) + '/read', {
          _token: '{{ csrf_token() }}'
      });
  }

  $(document).on('click', '.global-toast-close', function () {
      var hermesToast = $(this).closest('.global-hermes-toast');
      var userNotificationToast = $(this).closest('.global-user-notification-toast');

      if (hermesToast.length) {
          setDismissedHermesAlert(hermesToast.data('alert-id'));
          hideGlobalToast(hermesToast);
          return;
      }

      if (userNotificationToast.length) {
          markUserNotificationRead(userNotificationToast.data('notification-id'));
          hideGlobalToast(userNotificationToast);
          return;
      }

      hideGlobalToast($(this).closest('.global-twilio-toast, .global-client-details-toast, .global-user-notification-toast, .alert'));
  });

  $(document).on('click', '.global-user-notification-toast .global-toast-link', function () {
      var toast = $(this).closest('.global-user-notification-toast');
      markUserNotificationRead(toast.data('notification-id'));
  });

  $(function () {
      var initialAlerts = $('#globalHermesToastContainer .global-hermes-toast').map(function () {
          return {
              id: $(this).data('alert-id'),
              message: $(this).find('.global-hermes-toast-message').text()
          };
      }).get();
      renderHermesToasts(initialAlerts);
      autoHideGlobalToasts(document);
      refreshClientDetailsToasts();
      refreshUserNotificationToasts();
  });

  var hermesRefreshMs = parseInt($('#globalHermesToastContainer').data('refresh-ms'), 10) || 300000;
  setInterval(function () {
      refreshHermesToasts();
      refreshClientDetailsToasts();
      refreshUserNotificationToasts();
  }, hermesRefreshMs);

  $(document).ready(function () {
      $.get('/last-attendance', function (response) {
          if (response.data) {
              $('#attendance-name, #attendance-names').text(response.data.permanence_name);
          }
      });

      $.get('/last-operation', function (response) {
          if (response.data) {
              $('#operation-name, #operation-names').text(response.data.permanence_name);
          }
      });

      $.get('/last-parisgezgini', function (response) {
          if (response.data) {
              $('#parisgezgini-name, #parisgezgini-names').text(response.data.permanence_name);
          }
      });

      $("#languageswicher").change(function () {
          var locale = $(this).val();
          var _token = $("input[name=_token]").val();
          $.post("/language", { locale: locale, _token: _token }, function () {
              window.location.reload(true);
          });
      });

    

  });
</script>

@yield('footer')
@yield('scripts')




</body>
</html>
