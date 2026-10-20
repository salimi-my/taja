/*
 * CUSTOMIZATIONS MADE TO STANDARD SHADCN PAGINATION COMPONENT:
 *
 * 1. Changed PaginationPrevious/Next to icon-only buttons without text labels
 * 2. Simplified styling for icon-only buttons
 * 3. Removed text prop from PaginationPrevious/Next
 * 4. Changed variant logic - active uses "default" instead of "outline", inactive uses "outline" instead of "ghost"
 *
 * Changes improve consistency and simplify the API while maintaining full functionality.
 */

import * as React from "react";

import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  MoreHorizontalIcon,
} from "lucide-react";

function Pagination({ className, ...props }: React.ComponentProps<"nav">) {
  return (
    <nav
      role="navigation"
      aria-label="pagination"
      data-slot="pagination"
      className={cn("mx-auto flex w-full justify-center", className)}
      {...props}
    />
  );
}

function PaginationContent({
  className,
  ...props
}: React.ComponentProps<"ul">) {
  return (
    <ul
      data-slot="pagination-content"
      className={cn("flex items-center gap-1", className)}
      {...props}
    />
  );
}

function PaginationItem({ ...props }: React.ComponentProps<"li">) {
  return <li data-slot="pagination-item" {...props} />;
}

type PaginationLinkProps = {
  isActive?: boolean;
} & Pick<React.ComponentProps<typeof Button>, "size"> &
  // ORIGINAL: React.ComponentProps<"a">
  // CUSTOM: Changed to extend Button props instead of anchor props for better type safety
  React.ComponentProps<"button">;

function PaginationLink({
  className,
  isActive,
  // ORIGINAL: size = "icon"
  // CUSTOM: Changed default size to "default" for better spacing
  size = "default",
  ...props
}: PaginationLinkProps) {
  return (
    // ORIGINAL:
    // <Button
    //   asChild
    //   variant={isActive ? "outline" : "ghost"}
    //   size={size}
    //   className={cn(className)}
    // >
    //   <a
    //     aria-current={isActive ? "page" : undefined}
    //     data-slot="pagination-link"
    //     data-active={isActive}
    //     {...props}
    //   />
    // </Button>
    // CUSTOM: Changed to use Button component instead of anchor with buttonVariants and default variant for active link
    <Button
      aria-current={isActive ? "page" : undefined}
      variant={isActive ? "default" : "outline"}
      size={size}
      className={cn("h-8 min-w-8 px-2", className)}
      {...props}
    />
  );
}

function PaginationPrevious(
  {
    className,
    // ORIGINAL: text = "Previous",
    // CUSTOM: Removed text prop to simplify component
    ...props
  }: React.ComponentProps<typeof PaginationLink>,
  // ORIGINAL: & { text?: string }
  // CUSTOM: Removed text prop to simplify component
) {
  return (
    <PaginationLink
      aria-label="Go to previous page"
      // ORIGINAL: size="default" with text content
      // CUSTOM: Changed to icon size for icon-only button
      size="icon"
      // ORIGINAL: className={cn("pl-2!", className)}
      // CUSTOM: Simplified styling for icon-only button
      className={cn("h-8 min-w-8 px-1.5", className)}
      {...props}
    >
      {/* ORIGINAL:
      <ChevronLeftIcon data-icon="inline-start" />
      <span className="hidden sm:block">{text}</span> */}
      {/* CUSTOM: Changed to use icon size for icon-only button */}
      <ChevronLeftIcon className="size-4" />
    </PaginationLink>
  );
}

function PaginationNext(
  {
    className,
    // ORIGINAL: text = "Next",
    // CUSTOM: Removed text prop to simplify component
    ...props
  }: React.ComponentProps<typeof PaginationLink>,
  // ORIGINAL: & { text?: string }
  // CUSTOM: Removed text prop to simplify component
) {
  return (
    <PaginationLink
      aria-label="Go to next page"
      // ORIGINAL: size="default" with text content
      // CUSTOM: Changed to icon size for icon-only button
      size="icon"
      // ORIGINAL: className={cn("pr-2!", className)}
      // CUSTOM: Simplified styling for icon-only button
      className={cn("h-8 min-w-8 px-1.5", className)}
      {...props}
    >
      {/* ORIGINAL:
      <span className="hidden sm:block">{text}</span>
      <ChevronRightIcon data-icon="inline-end" /> */}
      {/* CUSTOM: Changed to use icon size for icon-only button */}
      <ChevronRightIcon className="size-4" />
    </PaginationLink>
  );
}

function PaginationEllipsis({
  className,
  ...props
}: React.ComponentProps<"span">) {
  return (
    <span
      aria-hidden
      data-slot="pagination-ellipsis"
      className={cn(
        "flex size-9 items-center justify-center [&_svg:not([class*='size-'])]:size-4",
        className,
      )}
      {...props}
    >
      <MoreHorizontalIcon />
      <span className="sr-only">More pages</span>
    </span>
  );
}

export {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
};
