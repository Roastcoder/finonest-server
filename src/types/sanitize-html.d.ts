declare function sanitizeHtml(input: string, options?: any): string;

declare namespace sanitizeHtml {
  const defaults: {
    allowedTags: string[];
    allowedAttributes: Record<string, string[]>;
  };
}

export = sanitizeHtml;
