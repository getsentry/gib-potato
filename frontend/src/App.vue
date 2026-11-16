<template>
    <MainMenu />
    <div class="max-w-7xl mx-auto p-4 md:p-8">
        <RouterView />
    </div>
</template>

<script setup>
import MainMenu from '@/components/MainMenu.vue';
</script>
