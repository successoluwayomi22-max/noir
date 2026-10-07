# Flutter Auto-Run on Connected Device Rule

## Guideline
Whenever modifications or new updates are made to the Flutter mobile app (`mobile/` directory):
1. Check if a physical phone or active device is connected (e.g. via `flutter devices` or checking running Flutter tasks).
2. If Flutter is already running on the device, immediately trigger a hot reload (`r`) or hot restart (`R`) via the background task management tool.
3. If Flutter is not running on the connected phone, run `flutter run -d <deviceId>` on the connected device in the background.
4. Ensure the user never has to manually request running or restarting Flutter on their phone after updates.
