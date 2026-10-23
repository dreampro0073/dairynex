<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({
    token: String,
    email: String,
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () =>
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
</script>

<template>
    <Head title="Set password" />
    <AuthLayout title="Set a new password">
        <form @submit.prevent="submit">
            <div class="field">
                <label for="email">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="email"
                />
                <div
                    v-if="form.errors.email"
                    class="error-text"
                >
                    {{ form.errors.email }}
                </div>
            </div>
            <div class="field">
                <label for="password">New password (at least 12 characters)</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    minlength="12"
                    required
                    autocomplete="new-password"
                />
                <div
                    v-if="form.errors.password"
                    class="error-text"
                >
                    {{ form.errors.password }}
                </div>
            </div>
            <div class="field">
                <label for="confirmation">Confirm new password</label>
                <input
                    id="confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    minlength="12"
                    required
                    autocomplete="new-password"
                />
            </div>
            <div
                v-if="form.errors.token"
                class="error-text"
            >
                {{ form.errors.token }}
            </div>
            <button
                class="btn full-btn"
                :disabled="form.processing"
            >
                Reset password
            </button>
        </form>
    </AuthLayout>
</template>
