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