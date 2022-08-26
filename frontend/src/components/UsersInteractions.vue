<script setup lang="ts">
import { useInteractionsStore } from '@/stores/interactions';
import { storeToRefs } from 'pinia';

const interactionsStore = useInteractionsStore();
const { interactions } = storeToRefs(interactionsStore);
</script>

<template>
  <div class="sm:flex-auto mb-4">
    <h1 class="text-xl font-semibold text-gray-900">Interactions</h1>
    <ul
      role="list"
      class="divide-y divide-gray-200"
    >
      <li
        v-for="interaction in interactions.state"
        :key="interaction.id"
        class="py-4 flex first:pt-0 last:pb-0"
      >
        <img
          class="h-10 w-10 rounded-full"
          :src="interaction.sender_picture"
          alt=""
        />
        <div class="ml-3">
          <p class="text-sm font-medium text-gray-900">
            {{ interaction.sender_name }}
          </p>
          <p class="text-sm text-gray-500">
            Sends
            <span class="font-extrabold">{{ interaction.amount }}</span> to
            <span class="font-medium">{{ interaction.receiver_name }}</span>
          </p>
        </div>
      </li>
    </ul>
  </div>
</template>
