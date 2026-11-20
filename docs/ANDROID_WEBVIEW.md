# Android WebView keyboard integration

The web client uses `VisualViewport` only as a fallback for WebViews that pan content instead of resizing it. The preferred native configuration is resize mode. Do not add an inset listener that consumes IME insets or translates the WebView: that creates a second, competing keyboard offset.

## Android system Back button

The web app's navigation uses Inertia's browser history, and the admin layout also has an in-app back arrow. The Android Activity must send its system Back button to the WebView while the WebView has a previous page; otherwise Android finishes the Activity and closes the app. The Android Activity source is maintained by the mobile wrapper, not this Laravel repository. Add this callback where that Activity creates its `WebView`:

```kotlin
onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
    override fun handleOnBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            // Let Android close the Activity only when the WebView has no history.
            isEnabled = false
            onBackPressedDispatcher.onBackPressed()
            isEnabled = true
        }
    }
})
```

Import `androidx.activity.OnBackPressedCallback`. Keep the callback active for the lifetime of the Activity and use the same WebView instance that loads DairyNex. This sends system Back to the previous Inertia/browser entry; the app exits only at the first page.

## Host Activity

In the manifest, use resize mode on the Activity hosting DairyNex:

```xml
<activity
    android:name=".DairyNexActivity"
    android:windowSoftInputMode="adjustResize" />
```

For an edge-to-edge Activity, leave IME insets unconsumed for the WebView. Apply system-bar padding to a native parent only; do not apply IME bottom padding or a translation to both the parent and the WebView.

```kotlin
// Correct: system bars may be handled by the native chrome, IME is not consumed.
ViewCompat.setOnApplyWindowInsetsListener(nativeChrome) { view, insets ->
    val bars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
    view.setPadding(bars.left, bars.top, bars.right, 0)
    insets
}
```

Avoid `adjustPan`, `SOFT_INPUT_ADJUST_NOTHING`, `WindowInsetsCompat.Type.ime()` consumption, manual `webView.translationY`, and JavaScript-driven `blur()` calls during viewport changes.

## Device test checklist

Use a physical Android device or emulator with the production WebView, test both portrait and landscape, and repeat after rotating with the keyboard open.

1. Login: focus the last password field; it and **Sign in** remain reachable. Close the keyboard and confirm no layout jump.
2. Customer create and edit: focus the final address/subscription fields in the long form, change between inputs, and submit without focus loss.
3. Delivery entry: edit quantity and PIN, then use **Complete delivery** and **Skip delivery** while the keyboard is open.
4. Payments: complete amount/reference and submit **Record payment** with the keyboard open.
5. PIN verification: use the customer and delivery-boy flows; replacement PIN and validation errors remain visible.
6. Open the Platform login-details modal and any Bootstrap dropdown/modal supplied by future pages. Focus its last control, scroll internally, and close it; verify only the modal scrolls while open.

For every flow, verify that opening/closing the IME does not duplicate the page scroll, blur the active field, or flicker the keyboard. Desktop widths should retain their existing layout because the keyboard rules are limited to 768px and below.
