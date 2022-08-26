import { useAsyncState } from '@vueuse/core'
import { defineStore } from 'pinia'
import params from '../../config/parameters'
import { defaultOptions } from '../utils/api'

export interface User {
  id: string;
  count: number;
  slack_name: string;
  slack_picture: string;
}

const users = {
  get: async () => {
    const res = await fetch(`${params.api.host}/users`, defaultOptions);
    const users = (await res.json())
      .filter((user: User) => user.count)
      .sort((a: User, b: User) => b.count - a.count);
    return users;
  }
}

export const useUsersStore = defineStore({
  id: 'users',
  state: () => ({
    users: useAsyncState<User[]>(
      users.get(),
      [],
    ),
  }),
})
