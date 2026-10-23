<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    deliveryBoy: { type: Object, default: null },
    routes: { type: Array, default: () => [] },
})

const form = useForm({
    name: props.deliveryBoy?.name ?? '',
    password: '',
    password_confirmation: '',
    mobile: props.deliveryBoy?.mobile ?? '',
    email: props.deliveryBoy?.email ?? '',
    route_id: props.deliveryBoy?.route_id ?? '',
    status: Number(props.deliveryBoy?.status ?? 1),
})

const submit = () =>
    props.deliveryBoy
        ? form.put(`/delivery-boys/${props.deliveryBoy.id}`)
        : form.post('/delivery-boys')
</script>

<template>
    <Head :title="deliveryBoy ? 'Edit Delivery Boy' : 'Add Delivery Boy'" />
    <AdminLayout>
        <template #title>{{ deliveryBoy ? 'Edit Delivery Boy' : 'Add Delivery Boy' }}</template>
        <template #subtitle>Manage delivery staff and route assignment</template>
        <Link
            href="/delivery-boys"
            class="btn secondary sm"
        >
            ← Back to Delivery Boys
        </Link>
        <form @submit.prevent="submit">
            <div class="panel">
                <div class="panel-head">
                    <h3>Delivery Boy Details</h3>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="boy-password">
                            {{
                                deliveryBoy
                                    ? 'New password (leave blank to keep current)'
                                    : 'Password (at least 12 characters)'
                            }}
                        </label>
                        <input
                            id="boy-password"
                            v-model="form.password"
                            type="password"
                            minlength="12"
                            :required="!deliveryBoy"
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
                        <label for="boy-password-confirmation">Confirm password</label>
                        <input
                            id="boy-password-confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            :required="!!form.password"
                            autocomplete="new-password"
                        />
                    </div>
                    <div class="field">
                        <label for="boy-name">Name</label>
                        <input
                            id="boy-name"
                            v-model="form.name"
                            required
                            maxlength="150"
                            autocomplete="name"
                            :aria-invalid="!!form.errors.name"
                        />
                        <div
                            v-if="form.errors.name"
                            class="error-text"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>
                    <div class="field">
                        <label for="boy-mobile">Mobile</label>
                        <input
                            id="boy-mobile"
                            v-model="form.mobile"
                            type="tel"
                            required
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            autocomplete="tel"
                            :aria-invalid="!!form.errors.mobile"
                        />
                        <div
                            v-if="form.errors.mobile"
                            class="error-text"
                        >
                            {{ form.errors.mobile }}
                        </div>
                    </div>
                    <div class="field">
                        <label for="boy-email">Email (optional)</label>
                        <input
                            id="boy-email"
                            v-model="form.email"
                            type="email"
                            maxlength="150"
                            autocomplete="email"
                            :aria-invalid="!!form.errors.email"
                        />
                        <div
                            v-if="form.errors.email"
                            class="error-text"
                        >
                            {{ form.errors.email }}
                        </div>
                    </div>
                    <div class="field">
                        <label for="boy-route">Assigned Route</label>
                        <select
                            id="boy-route"
                            v-model="form.route_id"
                            :aria-invalid="!!form.errors.route_id"
                        >
                            <option value="">Unassigned</option>
                            <option
                                v-for="route in routes"
                                :key="route.id"
                                :value="route.id"
                            >
                                {{ route.name }}
                            </option>
                        </select>
                        <div
                            v-if="form.errors.route_id"
                            class="error-text"
                        >
                            {{ form.errors.route_id }}
                        </div>
                    </div>
                    <div class="field">
                        <label for="boy-status">Status</label>
                        <select
                            id="boy-status"
                            v-model="form.status"
                        >
                            <option :value="1">Active</option>
                            <option :value="0">Inactive</option>
                        </select>
                        <div
                            v-if="form.errors.status"
                            class="error-text"
                        >
                            {{ form.errors.status }}
                        </div>
                    </div>
                </div>
            </div>
            <button
                class="btn"
                type="submit"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Saving…' : 'Save Delivery Boy' }}
            </button>
        </form>
    </AdminLayout>
</template>
