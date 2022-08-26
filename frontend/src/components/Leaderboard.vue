<script setup lang="ts">
// import Filter from "./Filter.vue";
import { useUsersStore } from '@/stores/users'
import { storeToRefs } from 'pinia'
import * as confetti from 'canvas-confetti'

const usersStore = useUsersStore();
const { users } = storeToRefs(usersStore);

const count = 200;
const defaults = {
  origin: { y: 0.7 }
};

const fire = (particleRatio: number, opts: any = {}) => {
  confetti.default(Object.assign({}, defaults, opts, {
    particleCount: Math.floor(count * particleRatio)
  }));
}

const onCountClick = (e: MouseEvent) => {
  fire(0.35, {
    spread: 100,
    decay: 0.91,
    scalar: 0.8,
    origin: { y: e.clientY / window.innerHeight, x: e.clientX / window.innerWidth }
  });
}
</script>

<template>
  <div class="sm:flex flex-col sm:items-start">
    <div class="sm:flex-auto mb-4">
      <h1 class="text-xl font-semibold text-gray-900">Leaderboard</h1>
      <p class="mt-2 text-sm text-gray-700">A list of all the users who received potatoes</p>
    </div>
    <!-- <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none self-end">
      <Filter />
    </div> -->
  </div>
  <div class="mt-2 flex flex-col">
    <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
      <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
          <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 w-4 text-left text-sm font-semibold text-gray-900 sm:pl-6">Rank</th>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-sm font-semibold text-gray-900">Count</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-if="users.isLoading" v-for="index in [1, 2, 3, 4]" :key="index">
                <td class="max-w-sm animate-pulse whitespace-nowrap py-3 pl-4 pr-3 text-sm sm:pl-6">
                  <span class="inline-flex px-4 py-2 text-lg font-semibold leading-5 text-gray-800 bg-gray-200 rounded-full"></span>
                </td>
                <td class="max-w-sm animate-pulse whitespace-nowrap py-3 pl-4 pr-3 text-sm sm:pl-6">
                  <span class="inline-flex px-16 py-2 text-lg font-semibold leading-5 text-gray-800 bg-gray-200 rounded-full"></span>
                </td>
                <td class="max-w-sm animate-pulse relative py-3 pl-3 pr-4 sm:pr-6 text-right">
                  <span class="inline-flex m-1 px-4 py-2 text-lg font-semibold leading-5 text-gray-800 bg-gray-200 rounded-full"></span>
                </td>
              </tr>
              <tr v-if="users.isReady" v-for="(person, index) in users.state" :key="person.id">
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  <span class="inline-flex px-2 text-lg font-semibold leading-5 text-gray-800">{{ index + 1 }}</span>
                </td>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                  <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0">
                      <img class="h-10 w-10 rounded-full" :src="person.slack_picture" alt="" />
                    </div>
                    <div class="ml-4">
                      <div class="font-medium text-gray-900">{{ person.slack_name }}</div>
                    </div>
                  </div>
                </td>
                <td class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right">
                  <span @click="onCountClick" class="select-none inline-flex px-2 text-lg font-semibold leading-5 text-gray-800">{{
                      person.count
                  }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
