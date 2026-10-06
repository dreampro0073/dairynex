<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    deliveryRoute: {
        type: Object,
        default: null,
    },

    deliveryBoys: {
        type: Array,
        default: () => [],
    },
})

const isEdit = computed(() => Boolean(props.deliveryRoute))

const form = useForm({
    name: props.deliveryRoute?.name ?? '',
    area_locality: props.deliveryRoute?.area_locality ?? '',
    delivery_boy_id: props.deliveryRoute?.delivery_boy_id ?? '',
    expected_stops: props.deliveryRoute?.expected_stops ?? '',
    status: Number(props.deliveryRoute?.status ?? 1),
})

const submit = () => {
    if (isEdit.value) {
        form.put(`/routes/${props.deliveryRoute.id}`)
        return
    }

    form.post('/routes')
}
</script>

<template>
    <AdminLayout>
        <template #title>
            {{ isEdit ? 'Edit Route' : 'Add New Route' }}
        </template>

        <template #subtitle>
            {{
                isEdit
                    ? 'Update delivery route'
                    : 'Create a delivery route'
            }}
        </template>

        <Link
            href="/routes"
            class="btn secondary sm pill"
            style="margin-bottom: 14px"
        >
            ← Back to Delivery Routes
        </Link>

        <form @submit.prevent="submit">
            <div class="panel">
                <div class="panel-head">
                    <h3>
                        {{ isEdit ? 'Edit Route' : 'Add New Route' }}
                    </h3>
                </div>

                <div class="form-grid">
                    <!-- Route Name -->
                    <div class="field">
                        <label>Route Name</label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Route 7"
                            :class="{
                                'field-error': form.errors.name
                            }"
                        >

                        <div
                            v-if="form.errors.name"
                            class="error-text"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Area -->
                    <div class="field">
                        <label>Area / Locality</label>

                        <input
                            v-model="form.area_locality"
                            type="text"
                            placeholder="e.g. Kankhal Main Road"
                            :class="{
                                'field-error': form.errors.area_locality
                            }"
                        >

                        <div
                            v-if="form.errors.area_locality"
                            class="error-text"
                        >
                            {{ form.errors.area_locality }}
                        </div>
                    </div>

                    <!-- Delivery Boy -->
                    <div class="field">
                        <label>Assigned Delivery Boy</label>

                        <select
                            v-model="form.delivery_boy_id"
                            :class="{
                                'field-error': form.errors.delivery_boy_id
                            }"
                        >
                            <option value="">
                                Select Delivery Boy
                            </option>

                            <option
                                v-for="boy in deliveryBoys"
                                :key="boy.id"
                                :value="boy.id"
                            >
                                {{ boy.name }}
                            </option>
                        </select>

                        <div
                            v-if="form.errors.delivery_boy_id"
                            class="error-text"
                        >
                            {{ form.errors.delivery_boy_id }}
                        </div>
                    </div>

                    <!-- Expected Stops -->
                    <div class="field">
                        <label>Expected Stops</label>

                        <input
                            v-model="form.expected_stops"
                            type="number"
                            min="0"
                            placeholder="e.g. 150"
                            :class="{
                                'field-error': form.errors.expected_stops
                            }"
                        >

                        <div
                            v-if="form.errors.expected_stops"
                            class="error-text"
                        >
                            {{ form.errors.expected_stops }}
                        </div>
                    </div>
                </div>

                <div class="toggle-row">
                    <div>
                        <div class="t-h">
                            Active
                        </div>

                        <div class="t-s">
                            Route available for delivery assignment
                        </div>
                    </div>

                    <button
                        type="button"
                        class="switch"
                        :class="{
                            on: Number(form.status) === 1
                        }"
                        @click="
                            form.status =
                                Number(form.status) === 1 ? 0 : 1
                        "
                    ></button>
                </div>
            </div>

            <button
                type="submit"
                class="btn pill full-btn"
                :disabled="form.processing"
            >
                <i class="bi bi-floppy"></i>

                {{
                    form.processing
                        ? 'Saving...'
                        : isEdit
                            ? 'Update Route'
                            : 'Save Route'
                }}
            </button>
        </form>
    </AdminLayout>
</template>