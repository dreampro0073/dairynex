<script setup>
import axios from 'axios'
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({ clientId: Number })

const emit = defineEmits(['close'])

const details = ref(null),
    error = ref(''),
    secret = ref(''),
    expiry = ref(''),
    busy = ref(false),
    copied = ref(false),
    dialog = ref(null)

let timer, previousFocus

const clearSecret = () => {
    secret.value = ''
    copied.value = false
    clearTimeout(timer)
}

const clearWhenHidden = () => {
    if (document.hidden) clearSecret()
}

const close = () => {
    clearSecret()
    emit('close')
}

const reset = async (admin) => {
    if (
        !window.confirm(
            `Reset ${admin.name}'s password? Their existing password and sessions will stop working.`,
        )
    )
        return
    clearSecret()
    error.value = ''
    busy.value = true
    try {
        const { data } = await axios.post(
            `/platform/clients/${props.clientId}/admins/${admin.id}/temporary-password`,
        )
        secret.value = data.password
        expiry.value = data.expires_at
        timer = setTimeout(clearSecret, 120000)
    } catch (e) {
        error.value = e.response?.data?.message || 'Unable to generate password.'
    } finally {
        busy.value = false
    }
}

const copy = async () => {
    try {
        await navigator.clipboard.writeText(secret.value)
        copied.value = true
    } catch {
        error.value = 'Copy is unavailable here. Select and copy the password manually.'
    }
}

const keydown = (event) => {
    if (event.key === 'Escape' && !busy.value) close()
    if (event.key !== 'Tab') return
    const nodes = [
        ...dialog.value.querySelectorAll('button:not(:disabled), a[href], input, [tabindex="0"]'),
    ]
    const first = nodes[0],
        last = nodes.at(-1)
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last?.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first?.focus()
    }
}

onMounted(async () => {
    previousFocus = document.activeElement
    window.addEventListener('pagehide', clearSecret)
    document.addEventListener('visibilitychange', clearWhenHidden)
    await nextTick()
    dialog.value?.focus()
    try {
        details.value = (await axios.get(`/platform/clients/${props.clientId}/login-details`)).data
    } catch (e) {
        error.value = e.response?.data?.message || 'Unable to load login details.'
    }
})

onBeforeUnmount(() => {
    clearSecret()
    window.removeEventListener('pagehide', clearSecret)
    document.removeEventListener('visibilitychange', clearWhenHidden)
    previousFocus?.focus()
})
</script>

<template>
    <div
        class="login-modal-backdrop"
        @click.self="!busy && close()"
        @keydown="keydown"
    >
        <section
            ref="dialog"
            class="login-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="login-title"
            tabindex="-1"
        >
            <div class="panel-head">
                <h3 id="login-title">View Login Details</h3>
                <button
                    class="btn secondary sm"
                    :disabled="busy"
                    @click="close"
                >
                    Close
                </button>
            </div>
            <div
                v-if="error"
                class="alert alert-danger"
                role="alert"
            >
                {{ error }}
            </div>
            <template v-if="details">
                <dl class="detail-grid">
                    <div>
                        <dt>Dairy</dt>
                        <dd>{{ details.dairy.name }} (#{{ details.dairy.id }})</dd>
                    </div>
                    <div>
                        <dt>Login URL</dt>
                        <dd>
                            <a
                                :href="details.login_url"
                                target="_blank"
                                rel="noopener"
                            >
                                {{ details.login_url }}
                            </a>
                        </dd>
                    </div>
                </dl>
                <div
                    v-for="admin in details.admins"
                    :key="admin.id"
                    class="account-card"
                >
                    <strong>{{ admin.name }}</strong>
                    <div>{{ admin.email || 'No email' }} · {{ admin.mobile }}</div>
                    <p class="muted">
                        {{ admin.status === 1 ? 'Active' : 'Inactive' }} Client Admin
                    </p>
                    <button
                        class="btn secondary sm"
                        :disabled="busy || admin.status !== 1 || details.dairy.status !== 1"
                        @click="reset(admin)"
                    >
                        Generate / Reset Temporary Password
                    </button>
                </div>
                <div
                    v-if="!details.admins.length"
                    class="empty"
                >
                    No Client Admin accounts. Add one from the dairy details page.
                </div>
                <div
                    v-if="secret"
                    class="login-secret"
                    role="status"
                >
                    <strong>Shown once — save this password now.</strong>
                    <code>{{ secret }}</code>
                    <div class="action-row">
                        <button
                            class="btn sm"
                            @click="copy"
                        >
                            {{ copied ? 'Copied' : 'Copy password' }}
                        </button>
                        <button
                            class="btn secondary sm"
                            @click="clearSecret"
                        >
                            Hide password
                        </button>
                    </div>
                    <p class="muted">
                        Valid until
                        {{ new Date(expiry).toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' }) }}
                        IST. Password change is required after login. This display clears after two
                        minutes.
                    </p>
                </div>
            </template>
            <div
                v-else-if="!error"
                class="empty"
            >
                Loading login details…
            </div>
        </section>
    </div>
</template>
