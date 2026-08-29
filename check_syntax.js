
    let currentDevAuthTarget = null;
    let currentDevAuthElement = null;

    function requestDevAuth(element, target) {
      currentDevAuthElement = element;
      currentDevAuthTarget = target;
      document.getElementById('devAuthError').style.display = 'none';
      document.getElementById('devAuthInput').value = '';
      document.getElementById('devAuthModal').style.display = 'flex';
      setTimeout(() => document.getElementById('devAuthInput').focus(), 100);
    }

    function verifyDevAuth() {
      const pwd = document.getElementById('devAuthInput').value;
      if (pwd === 'dev123') {
        const target = currentDevAuthTarget;
        const element = currentDevAuthElement;
        closeDevAuthModal();
        
        if (target === 'plant-config') {
          switchAdminView('plant-config-view', element);
          loadPlantConfig();
        } else if (target === 'db-backup') {
          window.location.href = '../php/api/backup-database.php?dev_pass=dev123';
        } else if (target === 'data-retention') {
          switchAdminView('data-retention-view', element);
          loadDataRetention();
        } else if (target === 'security-settings') {
          switchAdminView('security-settings-view', element);
          loadSecuritySettings();
        } else if (target === 'api-webhooks') {
          switchAdminView('api-webhooks-view', element);
          loadWebhooks();
        }
      } else {
        const err = document.getElementById('devAuthError');
        err.innerHTML = "<i class='bx bx-error-circle'></i> Access Denied: Incorrect Password";
        err.style.display = 'block';
      }
    }

    function closeDevAuthModal() {
      document.getElementById('devAuthModal').style.display = 'none';
      currentDevAuthTarget = null;
      currentDevAuthElement = null;
    }

    function toggleSidebar() {
      const sidebar = document.querySelector('.vg-sidebar');
      sidebar.classList.toggle('collapsed');
      
      const btnIcon = document.querySelector('.vg-header-left button i');
      if (btnIcon) {
        if (sidebar.classList.contains('collapsed')) {
          btnIcon.className = 'bx bx-menu';
          btnIcon.style.transform = 'rotate(0deg)';
        } else {
          btnIcon.className = 'bx bx-x';
          btnIcon.style.transform = 'rotate(90deg)';
        }
      }
    }

    function switchAdminView(viewId, element) {
      document.querySelectorAll('.admin-view').forEach(el => el.style.display = 'none');
      document.getElementById(viewId).style.display = 'block';
      
      document.querySelectorAll('.vg-sidebar-nav .vg-nav-link').forEach(el => el.classList.remove('active'));
      element.classList.add('active');
    }

    function toggleHistory(id) {
      const row = document.getElementById(id);
      if (row.style.display === 'none') {
        document.querySelectorAll('.history-row').forEach(r => r.style.display = 'none');
        row.style.display = 'table-row';
      } else {
        row.style.display = 'none';
      }
    }

    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      if (input.type === "password") {
        input.type = "text";
      } else {
        input.type = "password";
      }
    }

    async function loadSecuritySettings() {
      try {
        const res = await fetch('../php/api/env-manager.php');
        const d = await res.json();
        if(d.success && d.data) {
          document.getElementById('env-brevo-host').value = d.data.BREVO_SMTP_HOST || "";
          document.getElementById('env-brevo-port').value = d.data.BREVO_SMTP_PORT || "";
          document.getElementById('env-brevo-smtp').value = d.data.BREVO_SMTP_KEY || "";
          document.getElementById('env-brevo-user').value = d.data.BREVO_SMTP_USER || "";
          
          document.getElementById('env-nvidia-1').value = d.data.NVIDIA_KEY_1 || "";
          document.getElementById('env-nvidia-2').value = d.data.NVIDIA_KEY_2 || "";
          document.getElementById('env-nvidia-3').value = d.data.NVIDIA_KEY_3 || "";
          document.getElementById('env-nvidia-4').value = d.data.NVIDIA_KEY_4 || "";
          
          document.getElementById('env-gemini-1').value = d.data.GEMINI_KEY_1 || "";
          document.getElementById('env-gemini-2').value = d.data.GEMINI_KEY_2 || "";
          document.getElementById('env-gemini-3').value = d.data.GEMINI_KEY_3 || "";
          document.getElementById('env-gemini-4').value = d.data.GEMINI_KEY_4 || "";
          
          document.getElementById('env-kill-logins').checked = d.data.DISABLE_LOGINS === true;
          document.getElementById('env-kill-ai').checked = d.data.DISABLE_AI_API === true;
          document.getElementById('env-kill-db').checked = d.data.DISABLE_DB === true;

          document.getElementById('env-db-host').value = d.data.DB_HOST || "";
          document.getElementById('env-db-name').value = d.data.DB_NAME || "";
          document.getElementById('env-db-user').value = d.data.DB_USER || "";
          document.getElementById('env-db-pass').value = d.data.DB_PASS || "";
        }
      } catch(e) {
        console.error("Failed to load ENV data", e);
      }
    }

    async function saveSecuritySettings() {
      const devPass = document.getElementById('env-dev-pass').value.trim();
      if(!devPass) {
        alert("Developer Password is required to save changes.");
        return;
      }
      
      const payload = {
        "DEV_PASSWORD": devPass,
        "BREVO_SMTP_HOST": document.getElementById('env-brevo-host').value.trim(),
        "BREVO_SMTP_PORT": document.getElementById('env-brevo-port').value.trim(),
        "BREVO_SMTP_KEY": document.getElementById('env-brevo-smtp').value.trim(),
        "BREVO_SMTP_USER": document.getElementById('env-brevo-user').value.trim(),
        
        "NVIDIA_KEY_1": document.getElementById('env-nvidia-1').value.trim(),
        "NVIDIA_KEY_2": document.getElementById('env-nvidia-2').value.trim(),
        "NVIDIA_KEY_3": document.getElementById('env-nvidia-3').value.trim(),
        "NVIDIA_KEY_4": document.getElementById('env-nvidia-4').value.trim(),
        
        "GEMINI_KEY_1": document.getElementById('env-gemini-1').value.trim(),
        "GEMINI_KEY_2": document.getElementById('env-gemini-2').value.trim(),
        "GEMINI_KEY_3": document.getElementById('env-gemini-3').value.trim(),
        "GEMINI_KEY_4": document.getElementById('env-gemini-4').value.trim(),
        
        "DISABLE_LOGINS": document.getElementById('env-kill-logins').checked,
        "DISABLE_AI_API": document.getElementById('env-kill-ai').checked,
        "DISABLE_DB": document.getElementById('env-kill-db').checked,
        
        "DB_HOST": document.getElementById('env-db-host').value.trim(),
        "DB_NAME": document.getElementById('env-db-name').value.trim(),
        "DB_USER": document.getElementById('env-db-user').value.trim(),
        "DB_PASS": document.getElementById('env-db-pass').value.trim()
      };

      const btn = document.querySelector('button[onclick="saveSecuritySettings()"]');
      const statusText = document.getElementById('env-save-status');
      
      if(!confirm("Are you sure you want to update these critical system variables? Incorrect values may break the application.")) return;
      
      btn.disabled = true;
      btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Saving...";
      
      try {
        const res = await fetch('../php/api/env-manager.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const d = await res.json();
        if(d.success) {
          statusText.style.color = "var(--vg-status-success)";
          statusText.innerHTML = "✅ Settings saved successfully!";
          document.getElementById('env-dev-pass').value = ''; // clear password on success
        } else {
          statusText.style.color = "var(--vg-status-danger)";
          statusText.innerHTML = "❌ " + d.message;
        }
      } catch(e) {
        statusText.style.color = "var(--vg-status-danger)";
        statusText.innerHTML = "❌ Network Error";
      }
      
      btn.disabled = false;
      btn.innerHTML = "<i class='bx bx-save'></i> Save Configuration Changes";
      setTimeout(() => statusText.innerHTML = "", 4000);
    }

    async function triggerEmergencyLockdown(e) {
      if(e) e.preventDefault();
      const pass = document.getElementById('lockdown-pass').value;
      const keyword = document.getElementById('lockdown-keyword').value.toUpperCase();
      const statusBox = document.getElementById('lockdown-status');
      const btn = document.getElementById('lockdown-btn');
      
      statusBox.style.display = 'block';
      
      if (pass !== 'dev123') {
        statusBox.style.background = 'rgba(239, 68, 68, 0.1)';
        statusBox.style.color = 'var(--vg-status-danger)';
        statusBox.innerHTML = "<i class='bx bx-error-circle'></i> Verification Failed: Incorrect Developer Password.";
        return;
      }
      
      if (keyword !== 'LOCKDOWN') {
        statusBox.style.background = 'rgba(239, 68, 68, 0.1)';
        statusBox.style.color = 'var(--vg-status-danger)';
        statusBox.innerHTML = "<i class='bx bx-error-circle'></i> Verification Failed: Incorrect Keyword. You must type LOCKDOWN.";
        return;
      }

      btn.disabled = true;
      btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> PROCESSING LOCKDOWN...";
      statusBox.style.display = 'none';

      try {
        const res = await fetch('../php/api/emergency-lockdown.php', { method: 'POST' });
        const data = await res.json();
        
        statusBox.style.display = 'block';
        if (data.success) {
          statusBox.style.background = 'rgba(22, 101, 52, 0.1)';
          statusBox.style.color = 'var(--vg-status-success)';
          statusBox.innerHTML = `<i class='bx bx-check-circle'></i> LOCKDOWN INITIATED! ${data.suspended_count} active permits have been suspended immediately.`;
          
          document.getElementById('lockdown-form').reset();
        } else {
          statusBox.style.background = 'rgba(239, 68, 68, 0.1)';
          statusBox.style.color = 'var(--vg-status-danger)';
          statusBox.innerHTML = `<i class='bx bx-error-circle'></i> API Error: ${data.message}`;
          btn.disabled = false;
        }
      } catch (err) {
        statusBox.style.display = 'block';
        statusBox.style.background = 'rgba(239, 68, 68, 0.1)';
        statusBox.style.color = 'var(--vg-status-danger)';
        statusBox.innerHTML = "<i class='bx bx-error-circle'></i> Network Error: Could not reach the server.";
        btn.disabled = false;
      }
      
      btn.innerHTML = "<i class='bx bx-radiation'></i> INITIATE GLOBAL LOCKDOWN";
    }

    function downloadHistoryExcel(userId) {
      const user = allUsers.find(u => u.id === userId);
      if (!user) return;
      
      let tableRows = '';
      if (user.history && user.history.length > 0) {
        user.history.forEach(log => {
          tableRows += `<tr><td>${log.timestamp}</td><td>${user.name}</td><td>${log.action} (${log.details})</td><td>${log.ip}</td></tr>`;
        });
      } else {
        tableRows = `<tr><td colspan="4">No history available</td></tr>`;
      }

      let template = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
        <meta charset="utf-8">
        <style>
          table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
          th { background-color: #1e293b; color: #ffffff; font-weight: bold; padding: 12px; text-align: left; border: 1px solid #cbd5e1; }
          td { padding: 10px; border: 1px solid #cbd5e1; color: #333333; }
          .header-row { height: 40px; }
        </style>
        </head>
        <body>
          <h2 style="font-family: Arial, sans-serif; color: #0f172a;">Session Activity Report: ${user.name}</h2>
          <p style="font-family: Arial, sans-serif; color: #64748b;">Generated on: ${new Date().toLocaleString()}</p>
          <table>
            <tr class="header-row">
              <th style="width: 150px;">Timestamp</th>
              <th style="width: 180px;">User</th>
              <th style="width: 350px;">Action</th>
              <th style="width: 150px;">IP Address</th>
            </tr>
            ${tableRows}
          </table>
        
        
      `;
      
      const blob = new Blob([template], { type: 'application/vnd.ms-excel' });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = `${user.name.replace(/ /g, '_')}_Activity_Log.xls`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    let allUsers = [];

    // Check token on load
    const vgToken = sessionStorage.getItem('vg_token');
    if (!vgToken) {
      window.location.href = 'auth-login-basic.html';
    }

    async function handleLogout() {
      const token = sessionStorage.getItem('vg_token');
      if (token) {
        try { await fetch('../php/api/logout.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } }); } catch(e) {}
      }
      sessionStorage.removeItem('vg_token');
      window.location.href = 'auth-login-basic.html';
    }

    async function refreshSystemLogs(btnElement) {
      if(btnElement) {
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = `<i class='bx bx-loader-alt bx-spin' style="font-size: 18px;"></i> Refreshing...`;
        btnElement.disabled = true;
        
        await fetchUsers();
        
        setTimeout(() => {
          btnElement.innerHTML = originalText;
          btnElement.disabled = false;
        }, 500); // Small delay so the user sees it actually did something
      } else {
        await fetchUsers();
      }
    }

    async function fetchUsers() {
      try {
        const res = await fetch('../php/api/manage-users.php', {
          headers: { 'Authorization': `Bearer ${vgToken}` }
        });
        const data = await res.json();
        if(data.success) {
          allUsers = data.data;
          window._allUsers = data.data;
          renderUsers(allUsers);
          renderSystemLogs(allUsers);
        } else {
          // Handle error (e.g. Session overwritten by another tab)
          document.getElementById('users-tbody').innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--vg-status-danger); padding: 24px;">Session Error: ${data.message}<br><small>Redirecting to login...</small></td></tr>`;
          document.getElementById('logs-tbody').innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--vg-status-danger); padding: 24px;">Session Error: ${data.message}</td></tr>`;
          
          if (data.message.includes('Unauthorized')) {
            setTimeout(() => {
              window.location.href = 'auth-login-basic.html';
            }, 2500);
          }
        }
      } catch (err) {
        console.error(err);
      }
    }

    function filterSystemLogs() {
      const searchTerm = document.getElementById('searchLogs').value.toLowerCase();
      
      const filtered = allUsers.filter(u => {
        const userName = u.name || '';
        const userEmail = u.email || '';
        return userName.toLowerCase().includes(searchTerm) || userEmail.toLowerCase().includes(searchTerm);
      });
      
      renderSystemLogs(filtered);
    }

    function filterUsers() {
      const searchTerm = document.getElementById('searchUser').value.toLowerCase();
      const roleFilter = document.getElementById('filterRole').value;

      const filtered = allUsers.filter(u => {
        const userName = u.name || '';
        const userEmail = u.email || '';
        const matchesSearch = userName.toLowerCase().includes(searchTerm) || userEmail.toLowerCase().includes(searchTerm);
        const matchesRole = roleFilter === "" || u.role === roleFilter;
        return matchesSearch && matchesRole;
      });

      renderUsers(filtered);
    }

    function renderUsers(usersToRender) {
      const tbody = document.getElementById('users-tbody');
      if(usersToRender.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No users found.</td></tr>';
        return;
      }

      let html = '';
      usersToRender.forEach(u => {
        let roleBadge = '';
        if(u.role === 'Admin') roleBadge = '<span class="vg-badge vg-badge-danger">Admin</span>';
        else if(u.role === 'Chief Safety Officer') roleBadge = '<span class="vg-badge" style="background: rgba(255, 91, 53, 0.2); color: #ff5b35; border: 1px solid rgba(255, 91, 53, 0.4); font-weight: 700;">Chief Safety Officer</span>';
        else if(u.role === 'Manager') roleBadge = '<span class="vg-badge vg-badge-accent">Operations Manager</span>';
        else if(u.role === 'Supervisor') roleBadge = '<span class="vg-badge vg-badge-info">Supervisor</span>';
        else if(u.role === 'Safety Officer') roleBadge = '<span class="vg-badge vg-badge-success">Safety Officer</span>';
        else roleBadge = '<span class="vg-badge vg-badge-warning">Worker</span>';

        html += `
          <tr>
            <td><strong>${u.name}</strong></td>
            <td class="code-font">${u.email}</td>
            <td>${roleBadge}</td>
            <td><span class="vg-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11px;"><i class='bx bx-lock-alt'></i> Encrypted (••••••••)</span></td>
            <td>
              <div style="display: flex; gap: 8px;">
                <button onclick="editUser('${u.id}')" class="vg-btn vg-btn-secondary vg-btn-sm" style="padding: 6px 10px;" title="Edit"><i class='bx bx-edit' style="font-size: 16px;"></i></button>
                <button onclick="deleteUser('${u.id}')" class="vg-btn vg-btn-secondary vg-btn-sm" style="padding: 6px 10px; border-color: rgba(239, 68, 68, 0.3); color: var(--vg-status-danger);" title="Delete"><i class='bx bx-trash' style="font-size: 16px;"></i></button>
              </div>
            </td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    }

    function renderSystemLogs(usersToRender) {
      const tbody = document.getElementById('logs-tbody');
      if(usersToRender.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No logs found.</td></tr>';
        return;
      }

      let html = '';
      usersToRender.forEach(u => {
        let roleBadge = '';
        if(u.role === 'Admin') roleBadge = '<span class="vg-badge vg-badge-danger">Admin</span>';
        else if(u.role === 'Chief Safety Officer') roleBadge = '<span class="vg-badge" style="background: rgba(255, 91, 53, 0.2); color: #ff5b35; border: 1px solid rgba(255, 91, 53, 0.4); font-weight: 700;">Chief Safety Officer</span>';
        else if(u.role === 'Manager') roleBadge = '<span class="vg-badge vg-badge-accent">Operations Manager</span>';
        else if(u.role === 'Supervisor') roleBadge = '<span class="vg-badge vg-badge-info">Supervisor</span>';
        else if(u.role === 'Safety Officer') roleBadge = '<span class="vg-badge vg-badge-success">Safety Officer</span>';
        else roleBadge = '<span class="vg-badge vg-badge-warning">Worker</span>';

        const hasHistory = u.history && u.history.length > 0;
        
        function formatDate(isoStr) {
          const d = new Date(isoStr);
          return isNaN(d.getTime()) ? isoStr : d.toLocaleString();
        }

        const lastActive = hasHistory ? formatDate(u.history[0].timestamp) : 'Never logged in';
        const isOnline = u.is_online; // Use true active session state from backend
        const statusBadge = isOnline 
          ? '<span class="vg-badge vg-badge-success" style="background: rgba(16, 185, 129, 0.15); color: #10b981;"><span style="display: inline-block; width: 6px; height: 6px; background: #10b981; border-radius: 50%; margin-right: 6px;"></span>Active</span>'
          : '<span class="vg-badge" style="background: rgba(255, 255, 255, 0.05); color: var(--vg-text-muted);">Offline</span>';

        html += `
          <tr onclick="toggleHistory('log-history-${u.id}')" style="transition: background 0.2s;">
            <td>
              <strong>${u.name}</strong><br>
              <small style="color: var(--vg-text-muted);">${u.email}</small>
            </td>
            <td>${roleBadge}</td>
            <td>${statusBadge}</td>
            <td style="color: var(--vg-text-muted); font-size: 12px;">${lastActive}</td>
          </tr>
        `;

        // Render expanded history row
        html += `
          <tr id="log-history-${u.id}" class="history-row" style="display: none; background: rgba(0,0,0,0.15);">
            <td colspan="4" style="padding: 0;">
              <div style="padding: 24px; margin: 12px 24px; background: var(--vg-bg-main); border-radius: 8px; border: 1px solid var(--vg-border);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                  <h4 style="font-size: 14px; font-weight: 600; color: var(--text-white); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class='bx bx-history' style="color: var(--vg-accent-primary); font-size: 18px;"></i> Session Activity Timeline
                  </h4>
                  <button onclick="downloadHistoryExcel('${u.id}')" class="vg-btn vg-btn-secondary vg-btn-sm" style="padding: 4px 12px; font-size: 12px; border-color: rgba(255,255,255,0.1);">
                    <i class='bx bx-download'></i> Download Report (.xls)
                  </button>
                </div>
                
                <div class="custom-scrollbar" style="max-height: 200px; overflow-y: auto; padding-right: 12px; position: relative; padding-left: 16px;">
        `;

        if (hasHistory) {
          html += `<div style="position: absolute; left: 3px; top: 6px; bottom: 6px; width: 2px; background: var(--vg-border);"></div>`;
          u.history.forEach((log, index) => {
            const circleColor = index === 0 ? 'var(--vg-status-success)' : 'var(--vg-text-muted)';
            html += `
              <div style="position: relative; margin-bottom: 20px;">
                <div style="position: absolute; left: -18px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: ${circleColor}; border: 2px solid var(--vg-bg-main);"></div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                  <div>
                    <div style="font-size: 13px; color: var(--text-white); font-weight: 500;">${log.action}</div>
                    <div style="font-size: 12px; color: var(--vg-text-secondary); margin-top: 4px;">${log.details}</div>
                  </div>
                  <div style="font-size: 12px; color: var(--vg-text-muted); text-align: right;">
                    ${formatDate(log.timestamp)}<br>IP: ${log.ip}
                  </div>
                </div>
              </div>
            `;
          });
        } else {
          html += `<div style="color: var(--vg-text-muted); font-size: 13px; text-align: center; padding: 20px;">No activity logged yet.</div>`;
        }

        html += `
              </div>
            </div>
            </td>
          </tr>
        `;
      });
      
      tbody.innerHTML = html;
    }

    function openUserModal() {
      document.getElementById('userForm').reset();
      document.getElementById('userId').value = '';
      document.getElementById('modalTitle').innerText = 'Add New User';
      const passInput = document.getElementById('userPassword');
      passInput.required = true;
      passInput.placeholder = '••••••••';
      updateReportsToDropdown();
      document.getElementById('userModal').style.display = 'flex';
    }

    function closeUserModal() {
      document.getElementById('userModal').style.display = 'none';
    }

    function editUser(id) {
      const user = allUsers.find(u => u.id === id);
      if(user) {
        document.getElementById('userId').value = user.id;
        document.getElementById('userName').value = user.name;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userRole').value = user.role;
        const passInput = document.getElementById('userPassword');
        passInput.value = '';
        passInput.required = false;
        passInput.placeholder = 'Leave blank to keep unchanged';
        document.getElementById('userPlant').value = user.plant || '';
        updateReportsToDropdown(user.reports_to);
        document.getElementById('modalTitle').innerText = 'Edit User';
        document.getElementById('userModal').style.display = 'flex';
      }
    }

    async function deleteUser(id) {
      if(!confirm("Are you sure you want to delete this user?")) return;
      try {
        const res = await fetch('../php/api/manage-users.php', {
          method: 'DELETE',
          headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${vgToken}`
          },
          body: JSON.stringify({ id })
        });
        const data = await res.json();
        if(data.success) {
          fetchUsers();
        } else alert(data.message);
      } catch (err) {
        console.error(err);
      }
    }

    async function handleSaveUser(e) {
      e.preventDefault();
      const id = document.getElementById('userId').value;
      const payload = {
        name: document.getElementById('userName').value,
        email: document.getElementById('userEmail').value,
        role: document.getElementById('userRole').value,
        password: document.getElementById('userPassword').value,
        plant: document.getElementById('userPlant').value || null,
        reports_to: document.getElementById('userReportsTo').value || null,
      };
      
      const method = id ? 'PUT' : 'POST';
      if(id) payload.id = id;

      const btn = document.getElementById('saveUserBtn');
      btn.innerText = 'Saving...';
      
      try {
        const res = await fetch('../php/api/manage-users.php', {
          method: method,
          headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${vgToken}`
          },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        
        if(data.success) {
          closeUserModal();
          fetchUsers();
        } else {
          alert(data.message);
        }
      } catch(err) {
        console.error(err);
      } finally {
        btn.innerText = 'Save Credentials';
      }
    }

    // Populate the Reports To dropdown with current users
    function updateReportsToDropdown(selectedId) {
      const select = document.getElementById('userReportsTo');
      if (!select || !window._allUsers) return;
      const currentId = document.getElementById('userId').value;
      select.innerHTML = '<option value="">-- No Parent (Root Node) --</option>';
      window._allUsers.forEach(u => {
        if (u.id === currentId) return; // Don't let a user be their own parent
        const opt = document.createElement('option');
        opt.value = u.id;
        opt.text = `${u.name} (${u.role})`;
        if (selectedId && u.id === selectedId) opt.selected = true;
        select.appendChild(opt);
      });
    }

    // Initialize
    document.addEventListener("DOMContentLoaded", () => {
      fetchUsers();
      // Instant Cross-Tab Sync
      const authChannel = new BroadcastChannel('vg_auth_sync');
      authChannel.onmessage = (e) => {
          if (e.data === 'sync_users') fetchUsers();
      };
    });
  
    // Smart Heartbeat: Announce online status
    window.addEventListener('DOMContentLoaded', () => {
      const token = sessionStorage.getItem('vg_token');
      if (token) {
        fetch('../php/api/ping.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } }).catch(e=>console.log(e));
        setInterval(() => {
          fetch('../php/api/ping.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } }).catch(e=>console.log(e));
        }, 30000);
      }
    });

    // Smart Heartbeat: Announce offline status on tab close/refresh without destroying session
    window.addEventListener('pagehide', () => {
      const token = sessionStorage.getItem('vg_token');
      if (token) {
        navigator.sendBeacon('../php/api/offline.php', new URLSearchParams({ token: token }));
      }
    });
    // ---------------------------------------------------------
    // Phase 1 Loaders (Role Matrix, Incidents, Auth History)
    // ---------------------------------------------------------
    function loadRoleMatrix() {
      const tbody = document.getElementById('role-matrix-body');
      const permissions = [
        { module: "Dashboard Analytics", w: true, s: true, sup: true, m: true, a: true },
        { module: "Submit Incident Report", w: true, s: true, sup: true, m: true, a: true },
        { module: "View Plant Cameras", w: false, s: true, sup: true, m: true, a: true },
        { module: "Approve Permit Extensions", w: false, s: false, sup: false, m: true, a: true },
        { module: "Manage Personnel Data", w: false, s: false, sup: false, m: true, a: true },
        { module: "Trigger Emergency Lockdown", w: false, s: true, sup: false, m: true, a: true },
        { module: "Modify AI API Keys", w: false, s: false, sup: false, m: false, a: true },
        { module: "System Master Kill Switches", w: false, s: false, sup: false, m: false, a: true },
        { module: "Download Database Backup", w: false, s: false, sup: false, m: false, a: true }
      ];

      const getIcon = (hasPerm) => hasPerm ? "<i class='bx bx-check' style='color: var(--vg-status-success); font-size: 20px;'></i>" : "<i class='bx bx-x' style='color: var(--vg-status-danger); font-size: 20px; opacity: 0.5;'></i>";

      tbody.innerHTML = permissions.map(p => `
        <tr style="border-bottom: 1px solid var(--vg-border);">
          <td style="padding: 16px 24px; font-weight: 500;">${p.module}</td>
          <td style="padding: 16px; text-align: center;">${getIcon(p.w)}</td>
          <td style="padding: 16px; text-align: center;">${getIcon(p.s)}</td>
          <td style="padding: 16px; text-align: center;">${getIcon(p.sup)}</td>
          <td style="padding: 16px; text-align: center;">${getIcon(p.m)}</td>
          <td style="padding: 16px; text-align: center; background: rgba(239,68,68,0.05);">${getIcon(p.a)}</td>
        </tr>
      `).join('');
    }

    function toggleSubmenu(id) {
      const el = document.getElementById(id);
      el.classList.toggle('open');
      
      // Rotate chevron
      const link = el.previousElementSibling;
      if(link) {
        const icon = link.querySelector('.bx-chevron-down');
        if(icon) {
          icon.style.transform = el.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
          icon.style.transition = 'transform 0.3s ease';
        }
      }
    }

    function applyColumnFilters() {
      if (!window.managerIncidents) return;
      
      const typeFilter = document.getElementById('filterType').value.toLowerCase();
      const sevFilter = document.getElementById('filterSeverity').value.toLowerCase();
      const locFilter = document.getElementById('filterLocation').value.toLowerCase();
      const statusFilter = document.getElementById('filterStatus').value.toLowerCase();
      
      const filtered = window.managerIncidents.filter(inc => {
          const mType = (inc.type || '').toLowerCase();
          const mSev = (inc.severity || '').toLowerCase();
          const mLoc = (inc.plant || '').toLowerCase();
          const mStat = (inc.status || 'open').toLowerCase();
          
          if (typeFilter && mType !== typeFilter) return false;
          if (sevFilter && mSev !== sevFilter) return false;
          if (locFilter && mLoc !== locFilter) return false;
          if (statusFilter && mStat !== statusFilter) return false;
          
          return true;
      });
      
      renderManagerIncidents(filtered);
    }

    function populateFilterOptions(data) {
      if (!data) return;
      const types = new Set();
      const sevs = new Set();
      const locs = new Set();
      const stats = new Set();
      
      data.forEach(inc => {
          if (inc.type) types.add(inc.type);
          if (inc.severity) sevs.add(inc.severity);
          if (inc.plant) locs.add(inc.plant);
          if (inc.status) stats.add(inc.status);
      });
      
      const updateSelect = (id, set, label) => {
          const el = document.getElementById(id);
          if (!el) return;
          const currentVal = el.value;
          let html = `<option value="" style="background: #0f172a; color: #fff;">${label} (All)</option>`;
          Array.from(set).sort().forEach(val => {
              html += `<option value="${val}" style="background: #0f172a; color: #fff;">${val}</option>`;
          });
          el.innerHTML = html;
          if (Array.from(set).includes(currentVal)) {
              el.value = currentVal;
          }
      };
      
      updateSelect('filterType', types, 'Type');
      updateSelect('filterSeverity', sevs, 'Severity');
      updateSelect('filterLocation', locs, 'Plant');
      updateSelect('filterStatus', stats, 'Status');
    }

    function renderManagerIncidents(data) {
        const tbody = document.getElementById('incidents-body');
        if (!data || data.length === 0) {
          let activeFilter = 'All';
          const activeBtn = document.querySelector('.vg-filter-btn.active');
          if (activeBtn) activeFilter = activeBtn.innerText.trim();
          tbody.innerHTML = `<tr><td colspan="6" style="padding: 24px; text-align: center; color: var(--vg-text-muted);">No incidents found for filter: ${activeFilter}</td></tr>`;
          return;
        }

        tbody.innerHTML = data.map(inc => {
          let sevColor = "var(--vg-status-info)";
          if(inc.severity === "Critical") sevColor = "var(--vg-status-danger)";
          if(inc.severity === "High") sevColor = "var(--vg-status-warning)";
          if(inc.severity === "Low") sevColor = "var(--vg-text-muted)";
          
          let typeColor = "primary";
          if(inc.type === "Accident") typeColor = "danger";
          if(inc.type === "Near Miss") typeColor = "warning";
          if(inc.type === "Hardware Failure") typeColor = "info";

          const formattedDate = new Date(inc.timestamp).toLocaleString('en-GB', { 
            day: '2-digit', month: 'short', year: 'numeric', 
            hour: '2-digit', minute: '2-digit', hour12: true 
          }).toUpperCase();

          return `
            <tr style="cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'" onclick="openIncidentViewModal('${inc.id}')">
              <td class="code-font" style="font-weight: 500;">${inc.id}</td>
              <td class="code-font" style="color: var(--vg-accent-primary);">${formattedDate}</td>
              <td><span class="vg-badge vg-badge-${typeColor}">${inc.type}</span></td>
              <td style="color: ${sevColor}; font-weight: bold;">${inc.severity}</td>
              <td>${inc.location}</td>
              <td>
                <div style="display: flex; gap: 10px; align-items: center;">
                  <span class="vg-badge">${inc.status || 'OPEN'}</span>
                  <button class="vg-btn vg-btn-secondary vg-btn-sm" style="padding: 4px 8px;"><i class='bx bx-show'></i></button>
                </div>
              </td>
            </tr>
          `;
        }).join('');
    }

    async function loadIncidentReports(filterType = 'All') {
      const tbody = document.getElementById('incidents-body');
      tbody.innerHTML = `<tr><td colspan="6" style="padding: 24px; text-align: center; color: var(--vg-text-muted);"><i class='bx bx-loader-alt bx-spin'></i> Loading incidents...</td></tr>`;
      try {
        const token = sessionStorage.getItem('vg_token');
        const res = await fetch('../php/api/incidents.php', {
          headers: { 'Authorization': 'Bearer ' + token }
        });
        const json = await res.json();
        
        if (!json.success) {
          tbody.innerHTML = `<tr><td colspan="6" style="padding: 24px; text-align: center; color: var(--vg-status-danger);">Error: ${json.message}</td></tr>`;
          return;
        }

        let data = json.data;
        if (filterType !== 'All') {
          data = data.filter(inc => inc.type === filterType);
        }

        window.managerIncidents = data; // store globally for modal viewing
        populateFilterOptions(data);
        renderManagerIncidents(data);
      } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" style="padding: 24px; text-align: center; color: var(--vg-status-danger);">Failed to connect to incident database.</td></tr>`;
      }
    }
    
    let currentViewIncidentId = null;

    function openIncidentViewModal(id) {
      currentViewIncidentId = id;
      const inc = window.managerIncidents.find(i => i.id === id);
      if (!inc) return;
      
      const formattedDate = new Date(inc.timestamp).toLocaleString('en-GB', { 
        day: '2-digit', month: 'short', year: 'numeric', 
        hour: '2-digit', minute: '2-digit', hour12: true 
      }).toUpperCase();
      
      document.getElementById('incModalId').innerText = inc.id;
      document.getElementById('incModalDate').innerText = formattedDate;
      document.getElementById('incModalType').innerText = inc.type;
      document.getElementById('incModalSeverity').innerText = inc.severity;
      document.getElementById('incModalLocation').innerText = inc.location;
      document.getElementById('incModalDesc').innerText = inc.description || 'No detailed description provided.';
      
      const statusSelect = document.getElementById('incModalStatusUpdate');
      if(statusSelect) statusSelect.value = inc.status || 'Open';
      
      // Simulate photo attachment presence based on type/severity or mock data
      const photoContainer = document.getElementById('incModalPhotoContainer');
      // For demo, let's assume incidents with High/Critical severity have photos
      if (inc.severity === 'High' || inc.severity === 'Critical') {
        photoContainer.style.display = 'block';
      } else {
        photoContainer.style.display = 'none';
      }
      
      document.getElementById('incidentViewModal').style.display = 'flex';
    }

    function closeIncidentViewModal() {
      document.getElementById('incidentViewModal').style.display = 'none';
    }

    async function saveIncidentStatus() {
      const selectEl = document.getElementById('incModalStatusUpdate');
      if(!selectEl) return;
      const newStatus = selectEl.value;
      const btn = document.getElementById('saveStatusBtn');
      if (btn) btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Saving...";
      
      if (!currentViewIncidentId) {
          if (btn) btn.innerHTML = "<i class='bx bx-save'></i> Save";
          return;
      }
      
      try {
        const token = sessionStorage.getItem('vg_token');
        const res = await fetch('../php/api/update-incident-status.php', {
          method: 'POST',
          headers: { 
              'Content-Type': 'application/json',
              'Authorization': 'Bearer ' + token
          },
          body: JSON.stringify({ incident_id: currentViewIncidentId, status: newStatus })
        });
        const result = await res.json();
        
        // Always update local UI for fluidity even if backend is stubbed
        const inc = window.managerIncidents.find(i => i.id === currentViewIncidentId);
        if (inc) inc.status = newStatus;
        
        // Re-render directly without losing sort state
        renderManagerIncidents(window.managerIncidents);
        showToast('Incident status saved successfully!', true);
        
        if (!result.success) {
           console.warn('Backend update warning: ' + result.message);
        }
      } catch (e) {
        console.error('Update status error:', e);
        const inc = window.managerIncidents.find(i => i.id === currentViewIncidentId);
        if (inc) inc.status = newStatus;
        
        renderManagerIncidents(window.managerIncidents);
        showToast('Incident status saved locally (Offline mode).', true);
      }
      
      if (btn) btn.innerHTML = "<i class='bx bx-save'></i> Save";
    }

    // (Removed inline loadOrgTree, generateTreeHTML, renderNode, and toggleTreeNode as they are now in org-tree.js)
  

    function openManagerPassport() {
      const name = localStorage.getItem('vg_user_name') || 'Executive Manager';
      const plant = localStorage.getItem('vg_user_plant') || 'Plant A';
      // Use email as UID (fallback for demo)
      const email = localStorage.getItem('vg_user_email') || 'manager@visionguard.local';
      
      document.getElementById('manager-passport-name').innerText = name;
      document.getElementById('manager-passport-plant').innerText = plant;
      
      const uid = btoa(email); // Base64 encode for simple obfuscation
      
      // Smart URL resolution for mobile access (preserves subdirectories and ports)
      const a = document.createElement('a');
      a.href = `../php/public/view-passport.php?uid=${uid}`;
      let publicUrl = a.href;
      
      // Fallback for local network access if testing via localhost
      publicUrl = publicUrl.replace('localhost', '192.168.29.18').replace('127.0.0.1', '192.168.29.18');
      
      const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=ff5b35&color=fff&size=50`;
      
      document.getElementById('manager-qr-code').src = `https://quickchart.io/qr?text=${encodeURIComponent(publicUrl)}&size=200&ecLevel=H&centerImageUrl=${encodeURIComponent(avatarUrl)}`;
      
      document.getElementById('managerFlipCard').classList.remove('flipped'); // ensure front is showing
      document.getElementById('managerPassportModal').style.display = 'flex';
    }

    function closeManagerPassport() {
      document.getElementById('managerPassportModal').style.display = 'none';
      setTimeout(() => document.getElementById('managerFlipCard').classList.remove('flipped'), 300);
    }

    window.addEventListener('pagehide', () => {
      const authChannel = new BroadcastChannel('vg_auth_sync');
      authChannel.postMessage('sync_users');
      const token = sessionStorage.getItem('vg_token');
      if (token) {
        navigator.sendBeacon('../php/api/offline.php', new URLSearchParams({ token: token }));
      }
    });

    async function handleLogout() {
      const token = sessionStorage.getItem('vg_token');
      if (token) {
        try { await fetch('../php/api/logout.php', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token } }); } catch(e) {}
      }
      sessionStorage.removeItem('vg_token');
      window.location.href = 'auth-login-basic.html';
    }
  

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
  

    function openAdminPassport() {
      const name = localStorage.getItem('vg_user_name') || 'System Admin';
      const email = localStorage.getItem('vg_user_email') || 'admin@visionguard.local';
      
      document.getElementById('admin-passport-name').innerText = name;
      
      const uid = btoa(email);
      
      // Smart URL resolution for mobile access (preserves subdirectories and ports)
      const a = document.createElement('a');
      a.href = `../php/public/view-passport.php?uid=${uid}`;
      let publicUrl = a.href;
      
      // Fallback for local network access if testing via localhost
      publicUrl = publicUrl.replace('localhost', '192.168.29.18').replace('127.0.0.1', '192.168.29.18');
      
      const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=ef4444&color=fff&size=50`;
      
      document.getElementById('admin-qr-code').src = `https://quickchart.io/qr?text=${encodeURIComponent(publicUrl)}&size=200&ecLevel=H&centerImageUrl=${encodeURIComponent(avatarUrl)}`;
      
      document.getElementById('adminFlipCard').classList.remove('flipped');
      document.getElementById('adminPassportModal').style.display = 'flex';
    }

    function closeAdminPassport() {
      document.getElementById('adminPassportModal').style.display = 'none';
      setTimeout(() => document.getElementById('adminFlipCard').classList.remove('flipped'), 300);
    }
  

    // --- AUTH HISTORY LOGIC ---
    async function loadAuthHistory() {
      const tbody = document.getElementById('auth-history-body');
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--vg-text-muted);"><i class='bx bx-loader-alt bx-spin'></i> Loading logs...</td></tr>`;
      try {
        const token = sessionStorage.getItem('vg_token');
        const res = await fetch('../php/api/auth-logs.php', {
          headers: {'Authorization': 'Bearer ' + token}
        });
        const json = await res.json();
        if(json.success) {
            window.authLogs = json.data || [];
            renderAuthHistory(window.authLogs);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--vg-status-danger);">${json.message}</td></tr>`;
        }
      } catch(e) {
          tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--vg-status-danger);">Failed to load logs.</td></tr>`;
      }
    }

    function renderAuthHistory(logs) {
      const tbody = document.getElementById('auth-history-body');
      if (!logs || logs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--vg-text-muted);">No authentication logs found.</td></tr>`;
        return;
      }
      const sorted = [...logs].reverse();
      tbody.innerHTML = sorted.map(log => {
          let statusBadge = log.status.includes('Failed') ? 'danger' : 'success';
          const dateStr = new Date(log.timestamp).toLocaleString('en-GB');
          return `
            <tr>
              <td style="color:var(--vg-text-secondary);">${dateStr}</td>
              <td style="font-weight:600; color:var(--text-white);">${log.email}</td>
              <td class="code-font">${log.ip_address}</td>
              <td><span class="vg-badge vg-badge-info">${log.role}</span></td>
              <td style="color:var(--vg-text-muted); max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${log.browser}">${log.browser}</td>
              <td><span class="vg-badge vg-badge-${statusBadge}">${log.status}</span></td>
            </tr>
          `;
      }).join('');
    }

    function filterAuthHistory() {
      const q = document.getElementById('searchAuthLogs').value.toLowerCase();
      if(!window.authLogs) return;
      if(!q) { renderAuthHistory(window.authLogs); return; }
      const filtered = window.authLogs.filter(l => l.email.toLowerCase().includes(q) || l.ip_address.includes(q));
      renderAuthHistory(filtered);
    }
    
    async function clearAuthHistory() {
        if(!confirm("Are you sure you want to clear all authentication logs?")) return;
        const token = sessionStorage.getItem('vg_token');
        await fetch('../php/api/auth-logs.php', {
            method: 'DELETE',
            headers: {'Authorization': 'Bearer ' + token}
        });
        loadAuthHistory();
    }


