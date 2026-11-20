<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const errors = computed(() => {
    const values = Object.values(page.props.errors ?? {}).flatMap((value) =>
        Array.isArray(value) ? value : [value],
    )

    return [...new Set(values.filter((value) => typeof value === 'string' && value.trim()))]
})
</script>

<template>
    <div
        v-if="page.props.flash?.success"
        class="alert alert-success"
        role="status"
        aria-live="polite"
    >
        {{ page.props.flash.success }}
    </div>
    <div
        v-if="page.props.flash?.error"
        class="alert alert-danger"
        role="alert"
        aria-live="assertive"
    >
        {{ page.props.flash.error }}
    </div>
    <div
        v-if="errors.length"
        class="alert alert-danger form-error-summary"
        role="alert"
        aria-live="assertive"
    >
        <strong>Please check the following:</strong>
        <ul>
            <li v-for="error in errors" :key="error">{{ error }}</li>
        </ul>
    </div>
</template>

<style scoped>
.form-error-summary ul {
    margin: 6px 0 0;
    padding-left: 20px;
}
</style>
