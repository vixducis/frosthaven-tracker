import { Form, Head, router, setLayoutProps, useForm } from '@inertiajs/react';
import { useState } from 'react';
import CharacterController from '@/actions/App/Http/Controllers/CharacterController';
import ResourceTransferController from '@/actions/App/Http/Controllers/ResourceTransferController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import ResourceIcon from '@/components/resource-icon';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show as showCampaign } from '@/routes/campaigns';
import { show as showCharacter } from '@/routes/campaigns/characters';
import { update as updateCharacterResource } from '@/routes/campaigns/characters/resources';

type ResourceType = { value: string; label: string };

type CharacterResource = {
    id: number;
    resource_type: string;
    count: number;
};

type Character = {
    id: number;
    name: string;
    gold: number;
    experience: number;
    retired_at: string | null;
    resources: CharacterResource[];
};

type Campaign = {
    id: number;
    name: string;
};

function ResourceCounter({
    campaign,
    character,
    resource,
}: {
    campaign: Campaign;
    character: Character;
    resource: CharacterResource;
}) {
    function adjust(delta: number) {
        const newCount = Math.max(0, resource.count + delta);
        router.patch(
            updateCharacterResource.url({
                campaign: campaign.id,
                character: character.id,
                resourceType: resource.resource_type,
            }),
            { count: newCount },
            { preserveScroll: true },
        );
    }

    return (
        <div className="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                size="icon"
                onClick={() => adjust(-1)}
                disabled={resource.count === 0}
                aria-label="Decrease"
            >
                −
            </Button>
            <span className="w-8 text-center text-lg font-semibold tabular-nums">{resource.count}</span>
            <Button
                type="button"
                variant="outline"
                size="icon"
                onClick={() => adjust(1)}
                aria-label="Increase"
            >
                +
            </Button>
        </div>
    );
}

function TransferForm({
    campaign,
    character,
    resourceTypes,
}: {
    campaign: Campaign;
    character: Character;
    resourceTypes: ResourceType[];
}) {
    const resourceMap = Object.fromEntries(character.resources.map((r) => [r.resource_type, r]));

    const initialAmounts = Object.fromEntries(resourceTypes.map((t) => [t.value, 0]));

    const { data, setData, post, processing, errors, reset } = useForm<Record<string, number>>(initialAmounts);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        const transfers = Object.entries(data)
            .filter(([, amount]) => amount > 0)
            .map(([resource_type, amount]) => ({ resource_type, amount }));

        if (transfers.length === 0) {
            return;
        }

        const url = ResourceTransferController.store.url({ campaign: campaign.id, character: character.id });

        router.post(
            url,
            { transfers },
            {
                preserveScroll: true,
                onSuccess: () => reset(),
            },
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {resourceTypes.map((type) => {
                    const available = resourceMap[type.value]?.count ?? 0;

                    return (
                        <div key={type.value} className="space-y-1">
                            <Label htmlFor={`transfer-${type.value}`} className="flex items-center gap-1.5 text-sm">
                                <ResourceIcon type={type.value} className="size-4 shrink-0 text-muted-foreground" />
                                {type.label}
                                <span className="text-muted-foreground">({available})</span>
                            </Label>
                            <Input
                                id={`transfer-${type.value}`}
                                type="number"
                                min={0}
                                max={available}
                                value={data[type.value]}
                                onChange={(e) => setData(type.value, Math.min(Number(e.target.value), available))}
                                className="w-full"
                            />
                            {errors[type.value] && (
                                <InputError message={errors[type.value]} />
                            )}
                        </div>
                    );
                })}
            </div>

            <Button type="submit" disabled={processing}>
                {processing ? 'Transferring…' : 'Transfer to campaign pool'}
            </Button>
        </form>
    );
}

export default function CharacterShow({
    campaign,
    character,
    resourceTypes,
    isOwner,
}: {
    campaign: Campaign;
    character: Character;
    resourceTypes: ResourceType[];
    isOwner: boolean;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Campaigns', href: index() },
            { title: campaign.name, href: showCampaign.url(campaign) },
            { title: character.name, href: showCharacter.url({ campaign: campaign.id, character: character.id }) },
        ],
    });

    const resourceMap = Object.fromEntries(character.resources.map((r) => [r.resource_type, r]));
    const [confirmingRetire, setConfirmingRetire] = useState(false);
    const isRetired = character.retired_at !== null;

    function handleRetire() {
        router.post(CharacterController.retire.url({ campaign: campaign.id, character: character.id }));
    }

    return (
        <>
            <Head title={character.name} />

            <div className="space-y-10">
                {isRetired && (
                    <div className="rounded-lg border border-border bg-muted px-4 py-3 text-sm text-muted-foreground">
                        This character has been retired and is no longer playable.
                    </div>
                )}

                <section>
                    <Heading title={character.name} />

                    <Form
                        {...CharacterController.update.form({ campaign: campaign.id, character: character.id })}
                        options={{ preserveScroll: true }}
                        className="mt-4 grid gap-4 sm:grid-cols-2"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="gold">Gold</Label>
                                    <Input
                                        id="gold"
                                        name="gold"
                                        type="number"
                                        min={0}
                                        defaultValue={character.gold}
                                    />
                                    <InputError message={errors.gold} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="experience">Experience</Label>
                                    <Input
                                        id="experience"
                                        name="experience"
                                        type="number"
                                        min={0}
                                        defaultValue={character.experience}
                                    />
                                    <InputError message={errors.experience} />
                                </div>

                                <div className="sm:col-span-2">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Saving…' : 'Save'}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>

                <section>
                    <Heading title="Personal resources" description="Resources in your personal stash." />

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {resourceTypes.map((type) => {
                            const resource = resourceMap[type.value];

                            return (
                                <div
                                    key={type.value}
                                    className="flex flex-col gap-3 rounded-lg border border-border bg-card p-4"
                                >
                                    <div className="flex items-center gap-2">
                                        <ResourceIcon type={type.value} className="size-5 shrink-0 text-muted-foreground" />
                                        <span className="text-sm font-medium">{type.label}</span>
                                    </div>
                                    {resource && (
                                        <ResourceCounter
                                            campaign={campaign}
                                            character={character}
                                            resource={resource}
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </section>

                {!isRetired && (
                    <section className="rounded-lg border border-border bg-card p-6">
                        <Heading
                            variant="small"
                            title="Transfer to campaign pool"
                            description="Move resources from your stash to the shared campaign pool."
                        />

                        <div className="mt-4">
                            <TransferForm campaign={campaign} character={character} resourceTypes={resourceTypes} />
                        </div>
                    </section>
                )}

                {isOwner && !isRetired && (
                    <section className="rounded-lg border border-destructive/50 bg-card p-6">
                        <Heading
                            variant="small"
                            title="Retire character"
                            description="Retiring is permanent. All resources will be transferred to the campaign pool."
                        />

                        <div className="mt-4">
                            {confirmingRetire ? (
                                <div className="flex items-center gap-3">
                                    <span className="text-sm text-muted-foreground">Are you sure?</span>
                                    <Button type="button" variant="destructive" onClick={handleRetire}>
                                        Yes, retire
                                    </Button>
                                    <Button type="button" variant="outline" onClick={() => setConfirmingRetire(false)}>
                                        Cancel
                                    </Button>
                                </div>
                            ) : (
                                <Button type="button" variant="destructive" onClick={() => setConfirmingRetire(true)}>
                                    Retire character
                                </Button>
                            )}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}
