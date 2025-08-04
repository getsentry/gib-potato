<template>
    <MainMenu />
    <div class="max-w-7xl mx-auto p-4 md:p-8">
        <RouterView />
    </div>
</template>

<script>
import MainMenu from '@/components/MainMenu.vue';

export default {
    name: "App",
    components: { MainMenu },
};
</script>
