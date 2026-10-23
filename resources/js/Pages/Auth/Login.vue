<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({ clientId: Number })

const form = useForm({
    client_id: props.clientId ?? '',
    mobile: '',
    password: '',
})

const submit = () => form.post('/login', { onFinish: () => form.reset('password') })
</script>

<template>
    <Head title="Sign in" />
    <AuthLayout title="Sign in to your account">
        <form @submit.prevent="submit">
            <div class="field">
                <label for="dairy">Dairy ID (if provided by your administrator)</label>
                <input
                    id="dairy"
                    v-model="form.client_id"
                    type="number"
                    min="1"
                    inputmode="numeric"
                />
                <div
                    v-if="form.errors.client_id"
                    class="error-text"
                >
                    {{ form.errors.client_id }}
                </div>
            </div>
            <div class="field">
                <label for="mobile">Email or mobile</label>
                <input
                    id="mobile"
                    v-model="form.mobile"
                    type="text"
                    required
                    maxlength="150"
                    autocomplete="username"
                    autofocus
                />
                <div
                    v-if="form.errors.mobile"
                    class="error-text"
                    role="alert"
                >
                    {{ form.errors.mobile }}
                </div>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                />
                <div
                    v-if="form.errors.password"
                    class="error-text"
                >
                    {{ form.errors.password }}
                </div>
            </div>
            <button
                class="btn full-btn"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </button>
            <Link
                href="/forgot-password"
                class="link"
            >
                Forgot password?
            </Link>
        </form>
    </AuthLayout>
</template>
