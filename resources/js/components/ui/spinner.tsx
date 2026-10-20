/*
 * CUSTOMIZATIONS MADE TO STANDARD SHADCN SPINNER COMPONENT:
 *
 * 1. Added custom SVG spinner for better design consistency
 * 2. Removed import of Loader2Icon as it is not used
 *
 * Changes maintain full compatibility with standard shadcn Spinner API while providing better design consistency.
 */

import { cn } from "@/lib/utils";
// ORIGINAL: import { Loader2Icon } from "lucide-react";
// CUSTOM: Removed import of Loader2Icon as it is not used

function Spinner({ className, ...props }: React.ComponentProps<"svg">) {
  // ORIGINAL:
  // return (
  //   <Loader2Icon
  //     role="status"
  //     aria-label="Loading"
  //     className={cn("size-4 animate-spin", className)}
  //     {...props}
  //   />
  // );
  // CUSTOM: Added custom SVG spinner for better design consistency
  return (
    <svg
      className={cn("animate-spin", className)}
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      {...props}
    >
      <circle
        className="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        strokeWidth="4"
      />
      <path
        className="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      />
    </svg>
  );
}

export { Spinner };
