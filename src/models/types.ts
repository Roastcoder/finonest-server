export const USER_ROLES = {
  SUPERADMIN: 'superadmin',
  ADMIN: 'admin',
  EDITOR: 'editor',
  CONTRIBUTOR: 'contributor',
  CUSTOMER: 'customer', // For customer users
} as const;

export const PUBLISH_STATUS = {
  DRAFT: 'draft',
  PUBLISHED: 'published',
  SCHEDULED: 'scheduled',
} as const;

export const AUTH_DOMAIN = {
  ADMIN: 'admin',
  CUSTOMER: 'customer',
} as const;
