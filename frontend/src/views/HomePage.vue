<script setup lang="ts">
import UsersLeaderboard from '../components/UsersLeaderboard.vue';
</script>

<template>
  <div>
    <UsersLeaderboard />
    <footer class="p-2 flex justify-center">
      <span class="text-sm">
        &copy; {{ new Date().getFullYear() }} Sentry
      </span>
      <span class="mx-2"> &middot; </span>
      <a
        href="/logout"
        class="text-sm cursor-pointer"
      >
        Logout
      </a>
    </footer>
  </div>
</template>
