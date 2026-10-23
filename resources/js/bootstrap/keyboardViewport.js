/**
 * Keeps CSS layout measurements in sync with an Android WebView's visual
 * viewport. It deliberately never changes focus or blurs an input: Android
 * owns the IME and the browser remains the single scrolling surface.
 */
export function installKeyboardViewport() {
    if (!window.matchMedia('(max-width: 768px)').matches) return () => {}

    const viewport = window.visualViewport
    let frame
    let focusedElement = null

    const update = () => {
        frame = undefined
        const visibleHeight = viewport?.height ?? window.innerHeight
        // With adjustResize, innerHeight shrinks too and this is zero. With a
        // pan/legacy WebView, it exposes only the obscured portion.
        const coveredHeight = viewport
            ? Math.max(0, window.innerHeight - viewport.height - viewport.offsetTop)
            : 0
        const keyboardHeight = coveredHeight > 80 ? coveredHeight : 0

        document.documentElement.style.setProperty(
            '--app-viewport-height',
            `${Math.round(visibleHeight)}px`,
        )
        document.documentElement.style.setProperty(
            '--keyboard-height',
            `${Math.round(keyboardHeight)}px`,
        )
        if (keyboardHeight > 0) {
            document.documentElement.setAttribute('data-keyboard-open', '')
        } else {
            document.documentElement.removeAttribute('data-keyboard-open')
        }

        if (keyboardHeight > 0) ensureFocusedElementIsVisible(visibleHeight)
    }

    const scheduleUpdate = () => {
        if (!frame) frame = window.requestAnimationFrame(update)
    }

    const ensureFocusedElementIsVisible = (visibleHeight) => {
        const element = focusedElement
        if (!(element instanceof HTMLElement) || !element.isConnected) return
        const rect = element.getBoundingClientRect()
        // Avoid competing with WebView's normal focus scrolling. We only act
        // when a focused field is actually outside its visible viewport.
        if (rect.top >= 8 && rect.bottom <= visibleHeight - 12) return
        try {
            element.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'auto' })
        } catch {
            // Older WebViews only accept the legacy boolean signature.
            element.scrollIntoView(false)
        }
    }

    const onFocusIn = (event) => {
        const target = event.target
        focusedElement =
            target instanceof HTMLElement &&
            target.matches('input, select, textarea, [contenteditable="true"]')
                ? target
                : null
        scheduleUpdate()
    }

    const onFocusOut = () => {
        // Do not blur or scroll on focus changes; the next focusin, if any,
        // supplies the new field. This prevents IME flicker between controls.
        focusedElement = null
    }

    window.addEventListener('resize', scheduleUpdate, { passive: true })
    viewport?.addEventListener('resize', scheduleUpdate, { passive: true })
    viewport?.addEventListener('scroll', scheduleUpdate, { passive: true })
    document.addEventListener('focusin', onFocusIn)
    document.addEventListener('focusout', onFocusOut)
    update()

    return () => {
        if (frame) window.cancelAnimationFrame(frame)
        window.removeEventListener('resize', scheduleUpdate)
        viewport?.removeEventListener('resize', scheduleUpdate)
        viewport?.removeEventListener('scroll', scheduleUpdate)
        document.removeEventListener('focusin', onFocusIn)
        document.removeEventListener('focusout', onFocusOut)
    }
}
