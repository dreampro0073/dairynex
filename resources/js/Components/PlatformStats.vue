<script setup>
defineProps({ stats: Object })
</script>

<template>
    <div class="stat-row">
        <div
            v-for="([label, value], i) in Object.entries(stats)"
            :key="label"
            class="stat"
            :class="['', 'blue', 'butter', 'danger'][i % 4]"
        >
            <div class="label">{{ label }}</div>
            <div class="value">{{ value }}</div>
        </div>
    </div>
</template>
