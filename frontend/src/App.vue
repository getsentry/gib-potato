<template>
    <Menu />
    <div class="max-w-7xl mx-auto p-4 md:p-8">
        <RouterView />
    </div>
</template>

<script>
import Menu from '@/components/Menu.vue';

export default {
    name: "App",
    components: { Menu },
};
</script>
