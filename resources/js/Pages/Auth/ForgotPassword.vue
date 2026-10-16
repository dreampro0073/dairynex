<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
const form = useForm({ email: '' })
</script>
<template>
    <Head title="Reset password" />
    <AuthLayout title="Request a password reset">
        <p>Enter your account email. If your email is shared with another account or you have no email, contact your dairy administrator.</p>
        <form @submit.prevent="form.post('/forgot-password')">
            <div class="field"><label for="email">Email</label><input id="email" v-model="form.email" type="email" required autocomplete="email"><div v-if="form.errors.email" class="error-text">{{ form.errors.email }}</div></div>
            <button class="btn full-btn" :disabled="form.processing">Send reset link</button>
            <Link href="/login" class="link">Back to sign in</Link>
        </form>
    </AuthLayout>
</template>
