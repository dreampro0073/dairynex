<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
const props = defineProps({ customer: Object, logins: Array })
const login = useForm({ user_id: '', password: '', password_confirmation: '' })
const link = () => login.post(`/customers/${props.customer.id}/login`, { onFinish: () => login.reset('password', 'password_confirmation') })
</script>
<template>
    <Head :title="customer.name" />
    <AdminLayout>
        <template #title>{{ customer.name }}</template>
        <template #subtitle>Customer details</template>
        <template #header-action><Link :href="`/customers/${customer.id}/edit`" class="btn secondary sm">Edit customer</Link></template>
        <div class="panel">
            <div class="panel-head"><h3>Customer Details</h3><Link href="/customers" class="link">All customers</Link></div>
            <div class="form-grid">
                <div class="field"><label>Mobile</label><span>{{ customer.mobile || '—' }}</span></div>
                <div class="field"><label>Email</label><span>{{ customer.email || '—' }}</span></div>
                <div class="field"><label>Delivery PIN Verification</label><span class="badge" :class="customer.delivery_pin_required ? 'ok' : 'pending'">{{ customer.delivery_pin_required ? 'Enabled' : 'Disabled' }}</span><p>Applies to future deliveries. Existing deliveries retain their recorded policy.</p></div>
            </div>
        </div>
        <form v-if="!customer.user_id" class="panel" @submit.prevent="link">
            <div class="panel-head"><h3>Customer Login</h3></div>
            <div class="form-grid">
                <div class="field"><label for="login-user">Existing customer login</label><select id="login-user" v-model="login.user_id"><option value="">Create a login using this customer's mobile</option><option v-for="user in logins" :key="user.id" :value="user.id">{{ user.name }} · {{ user.mobile }}</option></select></div>
                <template v-if="!login.user_id"><div class="field"><label for="login-password">Password (at least 12 characters)</label><input id="login-password" v-model="login.password" type="password" required minlength="12" autocomplete="new-password"></div><div class="field"><label for="login-confirm">Confirm password</label><input id="login-confirm" v-model="login.password_confirmation" type="password" required autocomplete="new-password"></div></template>
                <div class="field"><button class="btn" :disabled="login.processing">Link customer login</button></div>
            </div><p v-if="Object.keys(login.errors).length" class="alert alert-danger" role="alert">{{ Object.values(login.errors).join(' ') }}</p>
        </form>
        <div v-else class="panel"><div class="panel-head"><h3>Customer login linked</h3></div></div>
    </AdminLayout>
</template>
