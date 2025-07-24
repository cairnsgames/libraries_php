In the /vscode directory add a settings.json file

{
  "rest-client.environmentVariables": {
    "$shared": {
        "app_id": "...",
        "apikey": "..."
    }
  }
}

use {{app_id}} to use appid in a .http file