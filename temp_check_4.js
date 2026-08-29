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