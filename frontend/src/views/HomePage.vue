<script setup lang="ts">
import UsersLeaderboard from '../components/UsersLeaderboard.vue';
import router from '../router';
import { useAccountStore } from '../stores/account';
import { storeToRefs } from 'pinia';

const accountStore = useAccountStore();
const { account } = storeToRefs(accountStore);

const navigation = [{ name: 'Home', href: '/', current: true }];
const logout = () => accountStore.logout();
</script>

<template>
  <div>
    <UsersLeaderboard />
    <footer class="p-2 flex justify-center">
      <span class="text-sm"> &copy; {{ new Date().getFullYear() }} Sentry </span>
      <span class="mx-2"> &middot; </span>
      <a
        class="text-sm cursor-pointer"
        @click="logout"
      >
        Logout
      </a>
    </footer>
  </div>
</template>
