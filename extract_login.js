const fs = require('fs');
const content = fs.readFileSync('found_json.txt', 'utf8');
const startIdx = content.indexOf('{');
const endIdx = content.lastIndexOf('}');
if (startIdx !== -1 && endIdx !== -1) {
  const jsonStr = content.substring(startIdx, endIdx + 1);
  try {
    const data = JSON.parse(jsonStr);
    if (data.ui_scope && data.ui_scope.dashboard_and_internal_pages) {
      // It's the requirement JSON. The user uploaded another JSON for the source files in the previous message!
    } else if (data.source_files) {
      const loginHtml = data.source_files['html/auth-login-basic.html'];
      if(loginHtml) {
        fs.writeFileSync('login_old.html', typeof loginHtml === 'object' ? loginHtml.content : loginHtml);
        console.log('Successfully saved login_old.html');
      } else {
        console.log('html/auth-login-basic.html not found in JSON');
      }
    } else {
      console.log('No source_files found in the JSON.');
    }
  } catch(e) {
    console.error(e);
  }
}
