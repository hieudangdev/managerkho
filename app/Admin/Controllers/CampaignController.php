<?php

namespace App\Admin\Controllers;

use App\Models\Campaign;
use App\Models\Domain;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CampaignController extends AdminController
{

    protected $title = 'BackLinks';

    protected function grid()
    {
        $grid = new Grid(new Campaign());

        $grid->column('id', __('ID'));
        $grid->column('name', __('Campaign Name'));
        $grid->column('text_ads', __('Text Ads'));
        $grid->column('link_ads', __('Link Ads'));
        $grid->column('description', __('Description'));
        $grid->domains("List Domains")->display(function () {
            $domains = $this->domains;
            if ($domains->isNotEmpty()) {
                $links = $domains->map(function ($domain) {
                    return "<a href=\"{$domain->domain_url}\" target=\"_blank\">{$domain->domain_url}</a>";
                });
        
                return $links->implode(', ');
            }
        
            return "No domains available";
        });
        $grid->column('run')->display(function () {
            $url = route('campaign.run', [
                'id' => $this->id,  // campaign id
            ]);
            return "<a href=\"{$url}\" style='display:block;width:100%;font-size:23px'><i class='fa fa-play-circle-o'></i></a>";
        });



        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Campaign());

        $form->text('name', __('Campaign Name'))->rules('required|string|max:255');
        $form->textarea('description', __('Description'))->rows(5);
        $form->text('text_ads', __('Text Ads'));
        $form->text('link_ads', __('Link Ads'));
        // Tạo checkbox list cho các domain
        $form->checkbox('domains', __('Domains'))->options(Domain::all()->pluck('domain_url', 'id'))
            ->rules('required');

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(Campaign::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Campaign Name'));
        $show->field('description', __('Description'));
        $show->field('text_ads', __('Text Ads'));
        $show->field('link_ads', __('Link Ads'));
        $show->field('domains', __('Domains'))->as(function ($domains) {
            return $domains->pluck('domain_url')->join(', ');
        });
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        $show->field('run')->unescape()->as(function () {
            $url = route('campaign.run', [
                'id' => $this->id,  // campaign id
            ]);
            return "<a href=\"{$url}\" class=\"btn btn-primary\">Run</a>";
        });

        return $show;
    }
    public function runCampaign($id)
    {
        $campaign = Campaign::findOrFail($id);
        $textAds = $campaign->text_ads;  // Lấy text_ads
        $linkAds = $campaign->link_ads;
        $client = new Client();

        // Gửi yêu cầu đến từng domain đã chọn
        foreach ($campaign->domains as $domain) {

            try {
                $response = $client->post("{$domain->domain_url}wp-admin/admin-ajax.php", [
                    'form_params' => [
                        'action' => 'update_keylink',
                        'text' => $textAds,
                        'custom_url' => $linkAds,
                    ],
                ]);

                if ($response->getStatusCode() == 200) {
                    // Thành công
                    admin_toastr('Campaign run successfully!');
                }
            } catch (Exception $e) {
                admin_toastr("Campaign faild! ", 'error');
                Log::error("Failed to send request to domain: {$domain->domain_url}. Error: " . $e->getMessage());
            }
        }

        // Chuyển hướng lại trang Show với thông báo thành công

        return redirect()->route(config('admin.route.prefix') . '.campaigns.index');
    }
}
