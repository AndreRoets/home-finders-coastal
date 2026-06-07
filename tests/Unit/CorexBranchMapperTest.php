<?php

namespace Tests\Unit;

use App\Services\Branches\BranchMapper;
use PHPUnit\Framework\TestCase;

class CorexBranchMapperTest extends TestCase
{
    public function test_it_maps_a_full_branch_payload(): void
    {
        $mapped = BranchMapper::map([
            'id' => 12,
            'trading_name' => 'Coastal Realty Margate',
            'tagline' => 'Your coast, your home',
            'address' => '12 Marina Drive, Margate',
            'phone' => '039 312 0000',
            'phone_label' => 'Office',
            'phone_secondary' => '082 000 0000',
            'phone_secondary_label' => 'After hours',
            'email' => 'margate@coastal.test',
            'ppra_number' => 'PPRA-MGT-1',
            'logo_url' => 'https://corex.test/branch.png',
            'agent_count' => 4,
            'listing_count' => 17,
            'agents' => [['id' => 88, 'name' => 'Thandi Mbeki', 'cell' => '+27 82 000 0000']],
        ]);

        $this->assertSame(12, $mapped['id']);
        $this->assertSame('Coastal Realty Margate', $mapped['tradingName']);
        $this->assertSame('039 312 0000', $mapped['phone']);
        $this->assertSame('0393120000', $mapped['phoneHref']);
        $this->assertSame('After hours', $mapped['phoneSecondaryLabel']);
        $this->assertSame('0820000000', $mapped['phoneSecondaryHref']);
        $this->assertSame('PPRA-MGT-1', $mapped['ppraNumber']);
        $this->assertSame('https://corex.test/branch.png', $mapped['logo']);
        $this->assertSame(4, $mapped['agentCount']);
        $this->assertSame(17, $mapped['listingCount']);
        $this->assertCount(1, $mapped['agents']);
        $this->assertSame('Thandi Mbeki', $mapped['agents'][0]['name']);
    }

    public function test_blank_fields_fall_back_to_agency_defaults_or_null(): void
    {
        $defaults = [
            'logo' => 'https://corex.test/agency.png',
            'contact' => ['phone' => '039 000 0000', 'email' => 'info@hfc.test', 'address' => 'Head Office'],
        ];

        $mapped = BranchMapper::map([
            'id' => 5,
            'trading_name' => 'Sparse Office',
            'agent_count' => 0,
            'listing_count' => 0,
            'agents' => [],
        ], $defaults);

        // Agency defaults fill the blanks that have an agency equivalent.
        $this->assertSame('https://corex.test/agency.png', $mapped['logo']);
        $this->assertSame('039 000 0000', $mapped['phone']);
        $this->assertSame('info@hfc.test', $mapped['email']);
        $this->assertSame('Head Office', $mapped['address']);

        // Branch-only optional fields collapse to null.
        $this->assertNull($mapped['tagline']);
        $this->assertNull($mapped['phoneLabel']);
        $this->assertNull($mapped['phoneSecondary']);
        $this->assertNull($mapped['ppraNumber']);
    }

    public function test_trading_name_defaults_when_missing(): void
    {
        $mapped = BranchMapper::map(['id' => 1, 'agents' => []]);

        $this->assertSame('Office', $mapped['tradingName']);
    }
}
