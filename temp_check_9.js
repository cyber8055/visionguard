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