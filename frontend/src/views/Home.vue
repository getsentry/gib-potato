<template>
    <Filter />
    <Leaderboard />
</template>

<script>
import Filter from '@/components/Filter.vue';
import Leaderboard from '@/components/Leaderboard.vue';

export default {
    name: 'Home',
    components: {
        Filter,
        Leaderboard
    }
};
</script>
