# Motion Tracking & Recording (Quick Guide)

## What It Does
- Watches the live camera feed continuously.
- Detects motion by comparing each frame to the previous frame.
- Starts recording automatically when motion crosses the configured threshold.
- Stops recording after motion is quiet for the configured idle delay.
- Saves clips in browser memory and allows download later.
- Supports manual non-stop recording (start/stop by operator).
- Supports optional no-sleep mode (Wake Lock) to reduce screen sleep interruptions.

## Security Model
- Public **Security Monitor** tab is available without opening admin panel.
- Any sensitive state change requires admin password:
  - Enable/disable motion tracking
  - Enable/disable auto-download
  - Change aggressiveness value
  - Change idle delay value
  - Save motion settings
  - Start/stop continuous recording
  - Enable/disable no-sleep mode
- Movement clips list is shown only inside admin settings.

## How Motion Detection Works
- A downscaled frame is sampled repeatedly.
- Pixel differences are measured against the previous sampled frame.
- If enough pixels change, motion is considered detected.
- **Aggressiveness** controls sensitivity:
  - Lower value = more sensitive (more triggers)
  - Higher value = less sensitive (fewer triggers)
- **Motion Stop Delay** controls how long motion must stay low before recording stops.

## Accuracy (Practical Expectations)
- Typical indoor, stable camera scene: good enough for monitoring and event capture.
- Not AI object detection; it is **pixel-change detection**.
- Accuracy depends heavily on:
  - Lighting stability
  - Camera noise quality
  - Camera angle and background movement (fans, screens, shadows)

## Common Drawbacks
- False positives from light flicker, shadows, monitor brightness changes.
- False negatives for very small/slow motion.
- Browser memory growth if many clips are kept without download/cleanup.
- Wake Lock is browser/device dependent and may not always be available.
- If camera stops or permission is revoked, motion capture stops.

## How to Reduce Drawbacks
- Keep camera fixed and scene lighting stable.
- Tune aggressiveness:
  - Increase if too many false triggers.
  - Decrease if motion is missed.
- Tune idle delay:
  - Increase to avoid chopped clips.
  - Decrease to reduce long unnecessary recordings.
- Periodically download and clear old clips.
- Use reliable power and keep browser tab active on dedicated kiosk devices.
- Consider server-side storage if long-term retention is required.

## Recommended Baseline
- Aggressiveness: `3` to `6`
- Motion Stop Delay: `2` to `4` seconds
- Auto-download: ON for high-volume security workflows, OFF for manual review workflows.
