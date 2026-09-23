<?php

use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Offer;
use App\Models\OfferPackage;
use App\Models\Price;
use App\Models\PriceType;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductGroup;
use App\Models\TaxRate;
use App\Models\Unit;
use SimpleXMLElement;

function load_xml(string $file): SimpleXMLElement
{
    import_log('load_xml', ['file' => $file, 'size' => filesize($file)]);

    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($file);

    if ($xml === false) {
        $errors = array_map(fn($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        import_log('load_xml FAILED', ['errors' => $errors]);
        throw new RuntimeException('XML error: ' . implode('; ', $errors));
    }

    return $xml;
}

function detect_type(SimpleXMLElement $xml): string
{
    if (isset($xml->ПакетПредложений)) {
        import_log('detected type: offers');
        return 'offers';
    }
    if (isset($xml->Каталог)) {
        import_log('detected type: products');
        return 'products';
    }
    import_log('detected type: UNKNOWN');
    throw new RuntimeException('Unknown XML type');
}

function text(SimpleXMLElement $node, string $name): ?string
{
    if (!isset($node->{$name})) {
        return null;
    }
    $v = trim((string)$node->{$name});
    return $v === '' ? null : $v;
}

function attr(SimpleXMLElement $node, string $name): ?string
{
    $v = trim((string)($node[$name] ?? ''));
    return $v === '' ? null : $v;
}

function importFile(string $path): void
{
    DB::connection()->transaction(function () use ($path) {
        $xml = load_xml($path);
        $type = detect_type($xml);

        if ($type === 'offers') {
            importOffers($xml);
        } else {
            importProducts($xml);
        }
    });
}

// =====================================================================
//  import.xml (каталог)
// =====================================================================

function importProducts(SimpleXMLElement $xml): void
{
    $groupsCount   = 0;
    $productsCount = 0;

    if (isset($xml->Классификатор->Группы)) {
        foreach ($xml->Классификатор->Группы->Группа as $groupNode) {
            saveGroup($groupNode, null);
            $groupsCount++;
        }
    }

    if (isset($xml->Каталог->Товары)) {
        foreach ($xml->Каталог->Товары->Товар as $productNode) {
            saveProduct($productNode);
            $productsCount++;
        }
    } else {
        import_log('importProducts: нет Каталог->Товары');
    }

    import_log('importProducts done', [
        'groups'   => $groupsCount,
        'products' => $productsCount,
    ]);
}

function saveGroup(SimpleXMLElement $node, ?int $parentId): void
{
    $externalId = text($node, 'Ид');
    if ($externalId === null) {
        return;
    }

    $group = ProductGroup::updateOrCreate(
        ['external_id' => $externalId],
        [
            'parent_id' => $parentId,
            'name'      => text($node, 'Наименование') ?? '',
        ]
    );

    if (isset($node->Группы)) {
        foreach ($node->Группы->Группа as $child) {
            saveGroup($child, $group->id);
        }
    }
}

function saveProduct(SimpleXMLElement $node): void
{
    $externalId = text($node, 'Ид');
    if ($externalId === null) {
        import_log('saveProduct: нет Ид, пропускаем');
        return;
    }

    $attrs = extractAttributes($node);
    $unit  = resolveUnit($node);
    $tax   = resolveTaxRate($node);

    $data = [
        'code'              => $attrs['Код'] ?? null,
        'article'           => text($node, 'Артикул'),
        'name'              => text($node, 'Наименование') ?? '',
        'full_name'         => $attrs['Полное наименование'] ?? null,
        'description'       => text($node, 'Описание'),
        'kind'              => $attrs['ТипНоменклатуры'] ?? null,
        'nomenclature_type' => $attrs['ВидНоменклатуры'] ?? null,
        'unit_id'           => $unit?->id,
        'tax_rate_id'       => $tax?->id,
        'is_active'         => 1,
    ];

    try {
        $product = Product::updateOrCreate(
            ['external_id' => $externalId],
            $data
        );
        import_log('saveProduct OK', [
            'external_id' => $externalId,
            'id'          => $product->id,
            'name'        => $data['name'],
        ]);
    } catch (Throwable $e) {
        import_log('saveProduct FAILED', [
            'external_id' => $externalId,
            'error'       => $e->getMessage(),
        ]);
        throw $e;
    }

    syncGroups($product, $node);
    syncAttributes($product, $attrs);
}

function extractAttributes(SimpleXMLElement $node): array
{
    $result = [];
    if (!isset($node->ЗначенияРеквизитов)) {
        return $result;
    }
    foreach ($node->ЗначенияРеквизитов->ЗначениеРеквизита as $req) {
        $name  = text($req, 'Наименование');
        $value = text($req, 'Значение');
        if ($name !== null) {
            $result[$name] = $value;
        }
    }
    return $result;
}

function resolveUnit(SimpleXMLElement $node): ?Unit
{
    if (!isset($node->БазоваяЕдиница)) {
        return null;
    }
    $unitNode = $node->БазоваяЕдиница;
    $code = attr($unitNode, 'Код');
    if ($code === null) {
        return null;
    }

    return Unit::updateOrCreate(
        ['code' => $code],
        [
            'name'               => attr($unitNode, 'НаименованиеПолное') ?? $code,
            'international_code' => attr($unitNode, 'МеждународноеСокращение'),
        ]
    );
}

function resolveTaxRate(SimpleXMLElement $node): ?TaxRate
{
    if (!isset($node->СтавкиНалогов->СтавкаНалога)) {
        return null;
    }
    $taxNode = $node->СтавкиНалогов->СтавкаНалога;
    $name = text($taxNode, 'Наименование') ?? 'НДС';
    $rate = text($taxNode, 'Ставка');
    if ($rate === null) {
        return null;
    }
    $rate = (float)$rate;

    // updateOrCreate по двум полям — ищем вручную
    $existing = TaxRate::where('name', $name)->where('rate', $rate)->first();
    if ($existing) {
        return $existing;
    }
    return TaxRate::create(['name' => $name, 'rate' => $rate]);
}

function syncGroups(Product $product, SimpleXMLElement $node): void
{
    if (!isset($node->Группы)) {
        return;
    }

    $ids = [];
    foreach ($node->Группы->Ид as $idNode) {
        $ext = trim((string)$idNode);
        if ($ext === '') {
            continue;
        }
        $group = ProductGroup::where('external_id', $ext)->first();
        if ($group) {
            $ids[] = $group->id;
        }
    }

    $product->product_groups()->sync($ids);
}

function syncAttributes(Product $product, array $attrs): void
{
    $product->attributes()->delete();
    foreach ($attrs as $name => $value) {
        $product->attributes()->create([
            'name'  => $name,
            'value' => $value,
        ]);
    }
}

// =====================================================================
//  offers.xml (пакет предложений)
// =====================================================================

function importOffers(SimpleXMLElement $xml): void
{
    if (!isset($xml->ПакетПредложений)) {
        import_log('importOffers: нет ПакетПредложений');
        throw new RuntimeException('ПакетПредложений не найден');
    }

    $pkg = $xml->ПакетПредложений;

    if (isset($pkg->ТипыЦен)) {
        foreach ($pkg->ТипыЦен->ТипЦены as $typeNode) {
            savePriceType($typeNode);
        }
    }

    $package = savePackage($pkg);

    $offersCount     = 0;
    $skippedProducts = 0;

    if (isset($pkg->Предложения)) {
        foreach ($pkg->Предложения->Предложение as $offerNode) {
            $skipped = saveOffer($offerNode, $package);
            if ($skipped) {
                $skippedProducts++;
            }
            $offersCount++;
        }
    } else {
        import_log('importOffers: нет Предложения');
    }

    import_log('importOffers done', [
        'offers'           => $offersCount,
        'skipped_products' => $skippedProducts,
    ]);
}

function savePriceType(SimpleXMLElement $node): void
{
    $externalId = text($node, 'Ид');
    if ($externalId === null) {
        return;
    }

    PriceType::updateOrCreate(
        ['external_id' => $externalId],
        [
            'name'         => text($node, 'Наименование') ?? '',
            'currency'     => text($node, 'Валюта') ?? 'руб',
            'tax_included' => (text($node, 'Налог/УчтеноВСумме') ?? 'false') === 'true' ? 1 : 0,
        ]
    );
}

function savePackage(SimpleXMLElement $pkg): OfferPackage
{
    $externalId = text($pkg, 'Ид');
    if ($externalId === null) {
        throw new RuntimeException('ПакетПредложений/Ид пустой');
    }

    return OfferPackage::updateOrCreate(
        ['external_id' => $externalId],
        [
            'name'                   => text($pkg, 'Наименование'),
            'catalog_external_id'    => text($pkg, 'ИдКаталога'),
            'classifier_external_id' => text($pkg, 'ИдКлассификатора'),
        ]
    );
}

/**
 * @return bool true — если товар не найден и предложение пропущено
 */
function saveOffer(SimpleXMLElement $node, OfferPackage $package): bool
{
    $externalId = text($node, 'Ид');
    if ($externalId === null) {
        return false;
    }

    $product = Product::where('external_id', $externalId)->first();
    if ($product === null) {
        import_log('saveOffer: product not found', ['external_id' => $externalId]);
        return true;
    }

    $offer = Offer::updateOrCreate(
        ['external_id' => $externalId],
        [
            'product_id' => $product->id,
            'package_id' => $package->id,
            'quantity'   => (float)(text($node, 'Количество') ?? 0),
        ]
    );

    import_log('saveOffer OK', [
        'external_id' => $externalId,
        'offer_id'    => $offer->id,
    ]);

    syncPrices($offer, $node);
    return false;
}

function syncPrices(Offer $offer, SimpleXMLElement $node): void
{
    if (!isset($node->Цены)) {
        return;
    }

    foreach ($node->Цены->Цена as $priceNode) {
        $typeExt = text($priceNode, 'ИдТипаЦены');
        if ($typeExt === null) {
            continue;
        }

        $priceType = PriceType::where('external_id', $typeExt)->first();
        if ($priceType === null) {
            continue;
        }

        Price::updateOrCreate(
            [
                'offer_id'      => $offer->id,
                'price_type_id' => $priceType->id,
            ],
            [
                'price'        => (float)(text($priceNode, 'ЦенаЗаЕдиницу') ?? 0),
                'currency'     => text($priceNode, 'Валюта') ?? 'руб',
                'coefficient'  => (float)(text($priceNode, 'Коэффициент') ?? 1),
                'presentation' => text($priceNode, 'Представление'),
            ]
        );
    }
}