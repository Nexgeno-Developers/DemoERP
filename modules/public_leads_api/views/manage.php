<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">Public API Settings</h4>
          </div>
          <div class="panel-body">
            <?php echo form_open(admin_url('public_leads_api')); ?>
              <input type="hidden" name="action" value="settings">
              <div class="form-group">
                <div class="checkbox checkbox-primary">
                  <input type="checkbox" name="enabled" id="pla-enabled" value="1" <?php echo $enabled ? 'checked' : ''; ?>>
                  <label for="pla-enabled">Enable public endpoint</label>
                </div>
              </div>
              <div class="form-group">
                <label for="rate_limit">Rate limit (requests)</label>
                <input type="number" min="0" class="form-control" name="rate_limit" id="rate_limit" value="<?php echo html_escape($rate_limit); ?>">
                <p class="text-muted mtop5">0 = unlimited</p>
              </div>
              <div class="form-group">
                <label for="rate_window">Per window (minutes)</label>
                <input type="number" min="1" class="form-control" name="rate_window" id="rate_window" value="<?php echo html_escape($rate_window); ?>">
                <p class="text-muted mtop5">Example: 120 requests per 10 minutes.</p>
              </div>
              <div class="form-group">
                <label for="blocked_ips">Blocked IPs</label>
                <textarea class="form-control" name="blocked_ips" id="blocked_ips" rows="3" placeholder="one or more IPs, comma or newline separated"><?php echo html_escape($blocked_ips); ?></textarea>
                <p class="text-muted mtop5">Requests from these IPs are rejected before rate limiting.</p>
              </div>
              <div class="form-group">
                <label for="allowed_custom_fields">Allowed custom field keys</label>
                <textarea class="form-control" name="allowed_custom_fields" id="allowed_custom_fields" rows="3" placeholder="e.g. tracking_id, campaign_source, budget"><?php echo html_escape($allowed_custom_fields); ?></textarea>
                <p class="text-muted mtop5">Leave empty to allow any extra payload key to become a Lead custom field. If filled, only the listed keys (comma or newline separated) will be auto-created when missing.</p>
              </div>
              <hr>
              <h5 class="bold">Inactive API notification</h5>
              <p class="text-muted mtop5">Send an email when an API key has not been used for a number of days.</p>
              <div class="form-group">
                <label for="inactive_days">Inactive days (X)</label>
                <input type="number" min="0" class="form-control" name="inactive_days" id="inactive_days" value="<?php echo (int) $inactive_days; ?>">
                <p class="text-muted mtop5">Notify when a key has not been used for more than this many days. Set to 0 to disable.</p>
              </div>
              <div class="form-group">
                <label for="inactive_notify_emails">Notification email addresses</label>
                <textarea class="form-control" name="inactive_notify_emails" id="inactive_notify_emails" rows="3" placeholder="admin@example.com, alerts@example.com"><?php echo html_escape($inactive_notify_emails); ?></textarea>
                <p class="text-muted mtop5">One or more email addresses to receive the inactive API notification (comma or newline separated).</p>
              </div>
              <button type="submit" class="btn btn-primary">Save Settings</button>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">Generate API Key</h4>
          </div>
          <div class="panel-body">
            <?php echo form_open(admin_url('public_leads_api')); ?>
              <input type="hidden" name="action" value="generate">
              <div class="form-group">
                <label for="label">Label</label>
                <input type="text" class="form-control" name="label" id="label" placeholder="e.g. WordPress site" required>
              </div>
              <button type="submit" class="btn btn-success">Generate Key</button>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">API Keys</h4>
          </div>
          <div class="panel-body table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Label</th>
                  <th>Key</th>
                  <th>Status</th>
                  <th>Usage</th>
                  <th>Last Used</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($keys as $key): ?>
                  <tr>
                    <td><?php echo html_escape($key['label']); ?></td>
                    <td><code><?php echo html_escape($key['api_key']); ?></code></td>
                    <td><?php echo $key['active'] ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Revoked</span>'; ?></td>
                    <td><?php echo (int) $key['usage_count']; ?></td>
                    <td><?php echo html_escape($key['last_used_at'] ?: '—'); ?></td>
                    <td><?php echo html_escape($key['created_at']); ?></td>
                    <td>
                      <?php if ($key['active']): ?>
                        <a href="<?php echo admin_url('public_leads_api/revoke/' . $key['id']); ?>" class="btn btn-xs btn-warning pla-revoke">Revoke</a>
                      <?php endif; ?>
                      <a href="<?php echo admin_url('public_leads_api/delete/' . $key['id']); ?>" class="btn btn-xs btn-danger pla-delete">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">Recent API Calls</h4>
          </div>
          <div class="panel-body table-responsive">
            <table class="table table-striped table-pla-logs" id="pla-logs-table" data-order-col="0" data-order-type="desc">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Status</th>
                  <th>Message</th>
                  <th>Lead ID</th>
                  <th>IP</th>
                  <th>At</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">API Usage Guide</h4>
          </div>
          <div class="panel-body">
            <p><strong>Endpoint 1</strong></p>
            <pre>POST <?php echo site_url('api/public/leads/store'); ?></pre>

            <p><strong>Endpoint 2</strong></p>
            <pre>POST <?php echo site_url('public_leads_api/public_api/store'); ?></pre>

            <p><strong>Headers</strong></p>
            <pre>X-API-KEY: &lt;your_api_key&gt;
Content-Type: application/json</pre>

            <p><strong>Payload (CRM lead fields)</strong></p>
            <p class="text-muted mtop5">Required: <code>name</code> (request is rejected if missing/blank). <code>status</code> and <code>source</code> fall back to your default CRM values if omitted. Extra keys become Lead custom fields only when whitelisted in settings; empty values are stored as <code>"-"</code> (or <code>0</code> for numeric fields).</p>

            <div class="table-responsive mtop10">
              <table class="table table-bordered table-condensed">
                <thead>
                  <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>name</code></td><td>string</td><td>Required lead name.</td></tr>
                  <tr><td><code>title</code>, <code>company</code>, <code>email</code>, <code>website</code>, <code>phonenumber</code>, <code>address</code>, <code>city</code>, <code>state</code>, <code>zip</code>, <code>description</code>, <code>default_language</code></td><td>string</td><td>Free text; trimmed, HTML stripped.</td></tr>
                  <tr><td><code>country</code></td><td>int</td><td>Country ID from CRM list.</td></tr>
                  <tr><td><code>assigned</code></td><td>int</td><td>Staff ID (see Reference &gt; Staff).</td></tr>
                  <tr><td><code>status</code>, <code>source</code></td><td>int or string</td><td>ID or exact name; falls back to defaults if not found.</td></tr>
                  <tr><td><code>lead_value</code></td><td>int</td><td>Numeric lead value.</td></tr>
                  <tr><td><code>tags</code></td><td>array or comma string</td><td>Example: <code>["web","inbound"]</code>.</td></tr>
                  <tr><td>Custom fields</td><td>string / number / array</td><td>Extra keys become lead custom fields if they match the allowed list (Settings &gt; Public API); arrays are JSON encoded.</td></tr>
                </tbody>
              </table>
            </div>
            <pre>{
  "name": "Jane Doe",                  // required lead name
  "title": "Marketing Manager",
  "company": "Acme Inc",
  "email": "jane@example.com",
  "website": "https://acme.com",
  "phonenumber": "+1 222 333 4444",
  "address": "123 Main St",
  "city": "Austin",
  "state": "TX",
  "zip": "73301",
  "country": 226,
  "description": "Project notes or context",
  "assigned": 5,
  "status": "New Lead",
  "source": "Website",
  "lead_value": 6500,
  "tags": ["web", "inbound"],
  "default_language": "en",
  "custom_field_example": "Extra keys become lead custom fields when allowed"
}</pre>

            <p><strong>Success Response</strong></p>
            <pre>{
  "status": true,
  "message": "Lead created successfully",
  "lead_id": 123
}</pre>

            <p><strong>Error Response</strong></p>
            <pre>{
  "status": false,
  "message": "Invalid API Key"
}</pre>

            <p><strong>cURL</strong></p>
            <pre>
curl -X POST "YOUR_ENDPOINT" \
  -H "X-API-KEY: YOUR_API_KEY" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "title": "Marketing Manager",
    "company": "Acme Inc",
    "email": "jane@example.com",
    "website": "https://acme.com",
    "phonenumber": "+1 222 333 4444",
    "address": "123 Main St",
    "city": "Austin",
    "state": "TX",
    "zip": "73301",
    "country": 226,
    "description": "Project notes or context",
    "assigned": 5,
    "status": "New Lead",
    "source": "Website",
    "lead_value": 6500,
    "tags": [
      "web",
      "inbound"
    ],
    "default_language": "en",
    "custom_field_example": "Extra keys become lead custom fields when allowed"
  }'</pre>

            <p class="text-muted mtop10">Accepts <code>application/json</code> or <code>multipart/form-data</code>. Each request creates one lead and logs the attempt.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">Reference Data</h4>
          </div>
          <div class="panel-body">
            <div class="row">
              <div class="col-md-3">
                <h5>Sources</h5>
                <ul class="list-unstyled mtop10 ref-list" data-limit="10">
                  <?php foreach ($sources as $source): ?>
                    <li><code><?php echo (int) $source['id']; ?></code> <?php echo html_escape($source['name']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="col-md-3">
                <h5>Statuses</h5>
                <ul class="list-unstyled mtop10 ref-list" data-limit="10">
                  <?php foreach ($statuses as $status): ?>
                    <li><code><?php echo (int) $status['id']; ?></code> <?php echo html_escape($status['name']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="col-md-3">
                <h5>Staff (Assignable)</h5>
                <ul class="list-unstyled mtop10 ref-list" data-limit="10">
                  <?php foreach ($staff as $member): ?>
                    <li><code><?php echo (int) $member['id']; ?></code> <?php echo html_escape($member['name']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="col-md-3">
                <h5>Tags</h5>
                <ul class="list-unstyled mtop10 ref-list" data-limit="10">
                  <?php foreach ($tags as $tag): ?>
                    <li><code><?php echo (int) $tag['id']; ?></code> <?php echo html_escape($tag['name']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
            <p class="text-muted mtop10">Use staff ID for <code>assigned</code>; other fields accept Names shown.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  (function() {
    var lists = document.querySelectorAll('.ref-list');
    lists.forEach(function(list) {
      var limit = parseInt(list.getAttribute('data-limit') || '10', 10);
      var items = Array.prototype.slice.call(list.querySelectorAll('li'));
      if (items.length <= limit) {
        return;
      }
      items.slice(limit).forEach(function(li) { li.style.display = 'none'; });

      var toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'btn btn-link btn-xs pleft0';
      toggle.textContent = 'Read more';
      toggle.dataset.state = 'collapsed';

      toggle.addEventListener('click', function() {
        var collapsed = toggle.dataset.state === 'collapsed';
        items.slice(limit).forEach(function(li) {
          li.style.display = collapsed ? '' : 'none';
        });
        toggle.textContent = collapsed ? 'Read less' : 'Read more';
        toggle.dataset.state = collapsed ? 'expanded' : 'collapsed';
      });

      list.parentNode.appendChild(toggle);
    });
  })();
</script>
<script>
  (function() {
    "use strict";
    function initPlaConfirmAlerts() {
      var $ = window.jQuery;
      if (!$) { return; }
      $(document).off("click.pla", ".pla-revoke").on("click.pla", ".pla-revoke", function(e) {
        if (!confirm("Are you sure you want to revoke this API key? It will no longer work for new requests.")) {
          e.preventDefault();
        }
      });
      $(document).off("click.pla", ".pla-delete").on("click.pla", ".pla-delete", function(e) {
        if (!confirm("Are you sure you want to delete this API key? This cannot be undone.")) {
          e.preventDefault();
        }
      });
    }
    if (window.jQuery) {
      initPlaConfirmAlerts();
    } else {
      document.addEventListener("DOMContentLoaded", function() {
        var attempts = 0;
        var iv = setInterval(function() {
          attempts++;
          if (window.jQuery) {
            clearInterval(iv);
            initPlaConfirmAlerts();
          } else if (attempts >= 80) {
            clearInterval(iv);
          }
        }, 50);
      });
    }
  })();
</script>
<script>
  (function() {
    "use strict";
    // Use core CRM helper for consistent DataTable options/translations.
    // Wait for jQuery + initDataTable to be available because this file
    // is included before init_tail() where scripts are loaded.
    function bootDataTable() {
      var $ = window.jQuery;
      if (!$ || typeof $.fn.DataTable !== 'function' || typeof window.initDataTable !== 'function') {
        return false;
      }

      var $table      = $('#pla-logs-table');
      if (!$table.length) { return true; }

      var orderCol    = parseInt($table.data('order-col') || 0, 10);
      var orderDir    = ($table.data('order-type') || 'desc').toString();
      var notSearch   = [];
      var notSortable = [];
      var serverParams = {};

      initDataTable(
        $table,
        admin_url + 'public_leads_api/logs',
        notSearch,
        notSortable,
        serverParams,
        [orderCol, orderDir]
      );

      return true;
    }

    if (!bootDataTable()) {
      var attempts = 0;
      var maxAttempts = 80; // 4 seconds (80 * 50ms)
      var interval = setInterval(function() {
        attempts++;
        if (bootDataTable() || attempts >= maxAttempts) {
          clearInterval(interval);
        }
      }, 50);
    }
  })();
</script>
<?php init_tail(); ?>
