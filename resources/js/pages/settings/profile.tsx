import { Form, Head, Link, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { AvatarUpload } from '@/components/avatar-upload';
import { ContentCard } from '@/components/content-card';
import DeleteUser from '@/components/delete-user';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <ContentCard
                title="Profile Information"
                description="Here's your profile section where you can view and manage your profile."
            >
                <div className="space-y-6">
                    <AvatarUpload user={auth.user} />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="flex flex-col gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Field data-invalid={!!errors.name}>
                                    <FieldLabel htmlFor="name">Name</FieldLabel>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={auth.user.name}
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                        aria-invalid={!!errors.name}
                                    />
                                    <FieldError>{errors.name}</FieldError>
                                </Field>

                                <Field data-invalid={!!errors.email}>
                                    <FieldLabel htmlFor="email">
                                        Email address
                                    </FieldLabel>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={auth.user.email}
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                        aria-invalid={!!errors.email}
                                    />
                                    <FieldError>{errors.email}</FieldError>
                                </Field>

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Your email address is
                                                unverified.{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                >
                                                    Click here to re-send the
                                                    verification email.
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    A new verification link has
                                                    been sent to your email
                                                    address.
                                                </div>
                                            )}
                                        </div>
                                    )}

                                <Field>
                                    <FieldLabel htmlFor="role">Role</FieldLabel>
                                    <Input
                                        id="role"
                                        value={
                                            auth.user.role === 'admin'
                                                ? 'Admin'
                                                : 'User'
                                        }
                                        readOnly
                                        className="cursor-not-allowed opacity-60"
                                    />
                                </Field>

                                <div className="mt-4 flex justify-end gap-2">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        {processing ? (
                                            <>
                                                <Spinner className="size-4" />
                                                Saving...
                                            </>
                                        ) : (
                                            'Save Changes'
                                        )}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </ContentCard>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
