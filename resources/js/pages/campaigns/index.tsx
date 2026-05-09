import { Form, Head, Link } from '@inertiajs/react';
import CampaignController from '@/actions/App/Http/Controllers/CampaignController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show } from '@/routes/campaigns';

type Campaign = {
    id: number;
    name: string;
};

export default function CampaignsIndex({ campaigns }: { campaigns: Campaign[] }) {
    return (
        <>
            <Head title="Campaigns" />

            <div className="space-y-8">
                <Heading title="Campaigns" description="Select a campaign or start a new one." />

                {campaigns.length > 0 && (
                    <ul className="space-y-2">
                        {campaigns.map((campaign) => (
                            <li key={campaign.id}>
                                <Link
                                    href={show(campaign)}
                                    className="flex items-center rounded-lg border border-border bg-card px-4 py-3 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                                >
                                    {campaign.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                <div className="rounded-lg border border-border bg-card p-6">
                    <Heading variant="small" title="New campaign" description="Give your campaign a name to get started." />

                    <Form {...CampaignController.store.form()} className="mt-4 space-y-4" resetOnSuccess>
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Campaign name</Label>
                                    <Input id="name" name="name" placeholder="e.g. The Frozen Crown" required />
                                    <InputError message={errors.name} />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating…' : 'Create campaign'}
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

CampaignsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Campaigns',
            href: index(),
        },
    ],
};
