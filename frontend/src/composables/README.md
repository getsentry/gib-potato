# TanStack Query Composables

This directory contains composables for managing API data using TanStack Query with the `queryOptions` pattern.

## Why queryOptions?

Using `queryOptions` instead of directly passing configuration to `useQuery` provides several benefits:

1. **Reusability**: The same query configuration can be used with `useQuery`, `prefetchQuery`, `ensureQueryData`, etc.
2. **Type Safety**: Better TypeScript inference and autocompletion
3. **Consistency**: Ensures the same query key and function are used everywhere

## Basic Usage

### In Composables

```javascript
import { useQuery, queryOptions } from '@tanstack/vue-query'
import api from '@/api'

// Define query options
export const userQueryOptions = () => queryOptions({
  queryKey: ['user'],
  queryFn: async () => {
    const response = await api.get('user')
    return response.data
  },
})

// Use in a composable
export const useUser = () => {
  return useQuery(userQueryOptions())
}
```

### In Components

```vue
<script setup>
import { useUser } from '@/composables/useUser'

const { data: user, isLoading } = useUser()
</script>
```

## Advanced Usage

### Prefetching

```javascript
import { useQueryClient } from '@tanstack/vue-query'
import { userQueryOptions } from '@/composables/useUser'

const queryClient = useQueryClient()

// Prefetch data
await queryClient.prefetchQuery(userQueryOptions())

// Ensure data is loaded (fetch only if not cached)
await queryClient.ensureQueryData(userQueryOptions())

// Get cached data
const cachedData = queryClient.getQueryData(userQueryOptions().queryKey)
```

### Route-based Prefetching

```javascript
// In router/index.js
{
  path: '/shop',
  component: Shop,
  beforeEnter: async () => {
    await queryClient.prefetchQuery(productsQueryOptions())
  }
}
```

### Invalidating Queries

```javascript
// After a mutation
queryClient.invalidateQueries({ queryKey: ['user'] })
```

## Available Composables

- `useUser()` - Current user data
- `useUsers()` - All users list
- `useUserProfile()` - User activity/profile data
- `useLeaderboard(filters)` - Leaderboard with filters
- `useProducts()` - Shop products
- `useCollection()` - User's collection
- `useQuickWins()` - Quick wins messages
- `useFilters()` - Local filter state management

## Query Options Exports

All query options are also exported for direct use:

- `userQueryOptions()`
- `usersQueryOptions()`
- `userProfileQueryOptions()`
- `leaderboardQueryOptions(filters)`
- `productsQueryOptions()`
- `collectionQueryOptions()`
- `quickWinsQueryOptions()` 