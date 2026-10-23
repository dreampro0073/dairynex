<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const submit = () => form.post('/change-password', { onFinish: () => form.reset() })
</script>

<template>
    <Head title="Change password" />
    <AuthLayout title="Choose a permanent password">
        <p>
            Change your temporary password before accessing your dairy. Use at least 12 characters.
        </p>
        <form @submit.prevent="submit">
            <div class="field">
                <label for="current">Temporary / current password</label>
                <input
                    id="current"
                    v-model="form.current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                />
                <span class="error-text">{{ form.errors.current_password }}</span>
            </div>
            <div class="field">
                <label for="new">New password</label>
                <input
                    id="new"
                    v-model="form.password"
                    type="password"
                    required
                    minlength="12"
                    autocomplete="new-password"
                />
                <span class="error-text">{{ form.errors.password }}</span>
            </div>
            <div class="field">
                <label for="confirm">Confirm new password</label>
                <input
                    id="confirm"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    minlength="12"
                    autocomplete="new-password"
                />
            </div>
            <button
                class="btn full-btn"
                :disabled="form.processing"
            >
                Change password
            </button>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="btn secondary full-btn"
            >
                Sign out
            </Link>
        </form>
    </AuthLayout>
</template>
