<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { useUsersStore } from '@/stores/users';
import { storeToRefs } from 'pinia';

const usersStore = useUsersStore();
const { users } = storeToRefs(usersStore);

// Small little easter egg so we can dogfood:
// If you type "taco" on the leaderboard page, an error is thrown.
let lastFourKeystrokes: string[] = [];
const recordKeystroke = (e: KeyboardEvent) => {
  lastFourKeystrokes.push(e.key);
  lastFourKeystrokes = lastFourKeystrokes.slice(-4);
  if (lastFourKeystrokes.join('') === 'taco') {
    throw new Error("We don't like Tacos!");
  }
};

onMounted(() => {
  window.addEventListener('keydown', recordKeystroke);
});
onUnmounted(() => {
  window.removeEventListener('keydown', recordKeystroke);
});
</script>

<template>
  <div class="max-w-4xl mx-auto p-8">
    <table class="w-full divide-y divide-zinc-300">
      <thead>
        <tr>
          <th
            scope="col"
            class="py-3.5 pr-3 text-left text-sm font-semibold"
          >
            Rank
          </th>
          <th
            scope="col"
            class="py-3.5 px-3 text-left text-sm font-semibold"
          >
            Person
          </th>
          <th
            scope="col"
            class="py-3.5 px-3 text-right text-sm font-semibold"
          >
            Sent
          </th>
          <th
            scope="col"
            class="relative py-3.5 pl-3 pr-4 text-right text-sm font-semibold"
          >
            Received
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <tr
          v-for="(person, index) in users.state"
          :key="person.id"
        >
          <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm">
            {{ index + 1 }}
          </td>
          <td class="whitespace-nowrap py-4 px-3 text-sm">
            <div class="flex items-center">
              <img
                class="h-10 w-10 rounded-full mr-4"
                :src="person.slack_picture"
              />
              <span class="text-ellipsis">
                {{ person.slack_name }}
              </span>
            </div>
          </td>
          <td
            class="whitespace-nowrap py-4 px-3 text-right text-sm"
          >
            {{ person.sent_count ?? 0 }}
          </td>
          <td
            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm"
          >
            {{ person.received_count ?? 0 }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
