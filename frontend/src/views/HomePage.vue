<script setup lang="ts">
import UsersLeaderboard from '../components/UsersLeaderboard.vue';
import router from '../router';
import { useAccountStore } from '../stores/account';
import { storeToRefs } from 'pinia';

const accountStore = useAccountStore();
const { account } = storeToRefs(accountStore);

const navigation = [{ name: 'Home', href: '/', current: true }];
const userNavigation = [
  {
    name: 'Logout',
    onclick: () => accountStore.logout(),
  },
];
</script>

<template>
  <div>
    <UsersLeaderboard />
  </div>
</template>
