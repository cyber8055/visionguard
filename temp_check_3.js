// Phase 2 Loaders (Power Tools)
    // ---------------------------------------------------------
    async function loadPlantConfig() {
      try {
        const res = await fetch('../data/plant_config.json');
        const data = await res.json();
        document.getElementById('pc-name').value = data.facility_name;
        document.getElementById('pc-zones').value = data.total_zones;
        document.getElementById('pc-shifts').value = data.operating_shifts;
        document.getElementById('pc-timezone').value = data.timezone;
        if (data.emergency_protocol) document.getElementById('pc-emergency').value = data.emergency_protocol;
        if (data.max_occupancy) document.getElementById('pc-occupancy').value = data.max_occupancy;
      } catch(e) { console.error(e); }
    }

    async function savePlantConfig(e) {
      e.preventDefault();
      const payload = {
        facility_name: document.getElementById('pc-name').value,
        total_zones: parseInt(document.getElementById('pc-zones').value),
        operating_shifts: parseInt(document.getElementById('pc-shifts').value),
        timezone: document.getElementById('pc-timezone').value,
        emergency_protocol: document.getElementById('pc-emergency').value,
        max_occupancy: parseInt(document.getElementById('pc-occupancy').value)
      };
      try {
        const res = await fetch('../php/api/plant-config.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
          alert('Global Plant Configuration saved successfully.');
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        alert('Failed to save configuration.');
      }
    }

    async function loadDataRetention() {
      try {
        const res = await fetch('../data/retention_policy.json');
        const data = await res.json();
        document.getElementById('ret-incidents').value = data.incident_reports_days;
        document.getElementById('ret-auth').value = data.auth_logs_days;
        document.getElementById('ret-camera').value = data.camera_footage_days;
        document.getElementById('ret-autopurge').checked = data.auto_purge_enabled;
      } catch(e) { console.error(e); }
    }

    async function saveDataRetention(e) {
      e.preventDefault();
      const payload = {
        incident_reports_days: parseInt(document.getElementById('ret-incidents').value),
        auth_logs_days: parseInt(document.getElementById('ret-auth').value),
        camera_footage_days: parseInt(document.getElementById('ret-camera').value),
        auto_purge_enabled: document.getElementById('ret-autopurge').checked
      };
      try {
        const res = await fetch('../php/api/data-retention.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
          alert('Data Retention Policies updated successfully.');
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        alert('Failed to save retention policies.');
      }
    }

    async function loadWebhooks() {
      const tbody = document.getElementById('webhooks-body');
      tbody.innerHTML = `<tr><td colspan="5" style="padding: 24px; text-align: center; color: var(--vg-text-muted);"><i class='bx bx-loader-alt bx-spin'></i> Loading webhooks...</td></tr>`;
      try {
        const res = await fetch('../data/webhooks.json');
        const data = await res.json();
        
        tbody.innerHTML = data.map(wh => {
          const isAct = wh.status === "Active";
          const statHtml = isAct ? `<span style="background: var(--vg-status-success)20; color: var(--vg-status-success); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Active</span>` 
                                 : `<span style="background: var(--vg-text-muted)20; color: var(--vg-text-muted); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Inactive</span>`;
          const evts = Array.isArray(wh.events) ? wh.events.join(", ") : (wh.events || "None");
          return `
          <tr style="border-bottom: 1px solid var(--vg-border);">
            <td style="padding: 16px 24px; font-family: monospace; font-weight: bold; color: var(--vg-accent-primary);">${wh.id || "N/A"}</td>
            <td style="padding: 16px; font-weight: 500;">${wh.name || "Unnamed"}</td>
            <td style="padding: 16px;">${evts}</td>
            <td style="padding: 16px;">${statHtml}</td>
            <td style="padding: 16px;">
              <button class="vg-btn vg-btn-secondary vg-btn-sm" onclick="alert('Editing Webhooks is handled through the backend API. Feature in progress.')">Edit</button>
            </td>
          </tr>
        `}).join('');
      } catch(e) {
        tbody.innerHTML = `<tr><td colspan="5" style="padding: 24px; text-align: center; color: var(--vg-status-danger);">Failed to load webhooks.</td></tr>`;
      }
    }

    function openWebhookModal() {
      document.getElementById('webhookModal').style.display = 'flex';
    }

    function closeWebhookModal() {
      document.getElementById('webhookModal').style.display = 'none';
      document.getElementById('webhookForm').reset();
    }

    async function saveWebhook(e) {
      e.preventDefault();
      const payload = {
        name: document.getElementById('wh-name').value,
        url: document.getElementById('wh-url').value,
        events: document.getElementById('wh-events').value.split(',').map(s => s.trim()),
        status: document.getElementById('wh-status').value
      };
      try {
        const res = await fetch('../php/api/webhooks.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
          alert('Webhook added successfully!');
          closeWebhookModal();
          loadWebhooks();
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        alert('Failed to save webhook.');
      }
    }