<script setup lang="ts">
import { useUsersStore } from '@/stores/users';
import { storeToRefs } from 'pinia';
import * as confetti from 'canvas-confetti';

const usersStore = useUsersStore();
const { users } = storeToRefs(usersStore);

const count = 200;
const defaults = {
  origin: { y: 0.7 },
};

const fire = (particleRatio: number, opts: unknown = {}) => {
  confetti.default(
    Object.assign({}, defaults, opts, {
      particleCount: Math.floor(count * particleRatio),
    })
  );
};

const onCountClick = (e: MouseEvent) => {
  fire(0.35, {
    spread: 100,
    decay: 0.91,
    scalar: 0.8,
    origin: {
      y: e.clientY / window.innerHeight,
      x: e.clientX / window.innerWidth,
    },
  });
};
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
            class="relative py-3.5 pl-3 pr-4 text-right text-sm font-semibold"
          >
            Potato
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <tr
          v-for="(person, index) in users.state"
          :key="person.id"
          @click="onCountClick"
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
            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm"
          >
            {{ person.count }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
