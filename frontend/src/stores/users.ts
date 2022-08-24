import { useAsyncState } from '@vueuse/core'
import { defineStore } from 'pinia'
import { mande } from 'mande'
import params from '../../config/parameters'

const users = mande(`${params.api.host}/users`)

export interface User {
  id: string;
  full_name: string;
  avatar_url: string;
  count: number;
}

export const useUsersStore = defineStore({
  id: 'users',
  state: () => ({
    users: useAsyncState<User[]>(
      users.get(''),
      [],
    ),
  }),
})
