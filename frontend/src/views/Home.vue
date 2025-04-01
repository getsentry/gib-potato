<template>
    <StocksLeaderboard />
    <Filter />
    <Leaderboard />
</template>

<script>
import Filter from '@/components/Filter.vue';
import Leaderboard from '@/components/Leaderboard.vue';
import StocksLeaderboard from '@/components/StocksLeaderboard.vue';

export default {
    name: 'Home',
    components: {
        Filter,
        Leaderboard,
        StocksLeaderboard,
    }
};
</script>
