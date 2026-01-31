/**
 * HTML Content Sanitization Utility
 * Sanitizes rich HTML content (from WYSIWYG editors) to prevent XSS attacks
 * while preserving safe formatting tags.
 */

import sanitizeHtmlLib from 'sanitize-html';

// Allowed HTML tags for rich content
const ALLOWED_TAGS = [
  'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
  'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
  'ul', 'ol', 'li',
  'a', 'blockquote', 'code', 'pre',
  'img', 'table', 'thead', 'tbody', 'tr', 'td', 'th',
  'div', 'span', 'section', 'article',
  'hr'
];

// Allowed attributes per tag
const ALLOWED_ATTRIBUTES: Record<string, string[]> = {
  a: ['href', 'title', 'target', 'rel'],
  img: ['src', 'alt', 'title', 'width', 'height'],
  table: ['border', 'cellpadding', 'cellspacing'],
  td: ['colspan', 'rowspan'],
  th: ['colspan', 'rowspan'],
  '*': ['class', 'id']
};

/**
 * Sanitize HTML string by removing dangerous tags and attributes
 * Uses sanitize-html library for production-ready XSS protection
 */
export function sanitizeHtml(html: string): string {
  if (!html || typeof html !== 'string') return '';

  return sanitizeHtmlLib(html, {
    allowedTags: ALLOWED_TAGS,
    allowedAttributes: ALLOWED_ATTRIBUTES,
    allowedSchemes: ['http', 'https', 'mailto'],
    allowedSchemesByTag: {
      a: ['http', 'https', 'mailto'],
      img: ['http', 'https', 'data']
    },
    // Remove all style attributes to prevent CSS injection
    allowedStyles: {},
    // Transform URLs to ensure they're safe
    transformTags: {
      a: (tagName: string, attribs: Record<string, string>) => {
        // Ensure external links open in new tab with rel="noopener noreferrer"
        if (attribs.href && (attribs.href.startsWith('http://') || attribs.href.startsWith('https://'))) {
          attribs.target = '_blank';
          attribs.rel = 'noopener noreferrer';
        }
        return { tagName, attribs };
      }
    }
  });
}

/**
 * Sanitize rich content fields in an object
 * Used for BlogPost content, Service overview, Page blocks, etc.
 */
export function sanitizeRichContent(obj: any, fields: string[] = ['content', 'overview', 'intro', 'description']): any {
  if (!obj || typeof obj !== 'object') return obj;

  if (Array.isArray(obj)) {
    return obj.map(item => sanitizeRichContent(item, fields));
  }

  const sanitized: any = {};
  for (const [key, value] of Object.entries(obj)) {
    if (fields.includes(key) && typeof value === 'string') {
      sanitized[key] = sanitizeHtml(value);
    } else if (typeof value === 'object' && value !== null) {
      // Recursively sanitize nested objects (e.g., blocks.props)
      sanitized[key] = sanitizeRichContent(value, fields);
    } else {
      sanitized[key] = value;
    }
  }

  return sanitized;
}

/**
 * Sanitize page blocks (for Page model)
 */
export function sanitizePageBlocks(blocks: any[]): any[] {
  if (!Array.isArray(blocks)) return [];

  return blocks.map(block => {
    if (block.props && typeof block.props === 'object') {
      return {
        ...block,
        props: sanitizeRichContent(block.props, ['content', 'heading', 'subheading', 'description', 'text'])
      };
    }
    return block;
  });
}
