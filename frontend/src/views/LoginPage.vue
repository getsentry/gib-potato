<script setup lang="ts">
import { buildSlackAuthUrl } from '../utils/slack';
import params from '../../config/parameters';

const slackAuthUrl = buildSlackAuthUrl(params.slack);
</script>

<template>
  <div class="h-full flex items-center justify-center">
    <div class="">
      <img
        src="/logo.png"
        class="w-32 mx-auto"
      />
      <h1 class="text-center text-3xl font-bold mb-16">Gib Potato</h1>
      <a
        :href="slackAuthUrl"
        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-amber-700 bg-amber-100 hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
      >
        Sign in with Slack
      </a>
    </div>
  </div>
</template>
