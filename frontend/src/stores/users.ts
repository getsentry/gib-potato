import { useAsyncState } from '@vueuse/core';
import { defineStore } from 'pinia';
import params from '../../config/parameters';
import { defaultOptions } from '../utils/api';

export interface User {
  id: string;
  received_count: number;
  sent_count: number;
  slack_name: string;
  slack_picture: string;
}

const users = {
  get: async () => {
    const res = await fetch(`${params.api.host}/users`, defaultOptions);
    const users = (await res.json());

    return users;
  },
};

export const useUsersStore = defineStore({
  id: 'users',
  state: () => ({
    users: useAsyncState<User[]>(users.get(), []),
  }),
});
