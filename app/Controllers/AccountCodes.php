<?php

namespace App\Controllers;

use App\Models\AccountCodeRepository;
use CodeIgniter\Exceptions\PageNotFoundException;

class AccountCodes extends BaseController
{
    protected AccountCodeRepository $repo;

    public function __construct()
    {
        $this->repo = new AccountCodeRepository();
    }

    public function index()
    {
        $initialCode = trim((string)$this->request->getGet('code'));
        $searchQuery = trim((string)$this->request->getGet('q'));
        $selectedGroup = trim((string)$this->request->getGet('group'));

        $accounts = $this->repo->getAllAccounts();
        $selectedAccount = null;

        if ($initialCode !== '') {
            $selectedAccount = $this->repo->findByCode($initialCode);
        }

        $data = [
            'title'            => 'Kamus Kode Akun Belanja RKAKL — Divhumas Polri',
            'meta'             => $this->repo->getMeta(),
            'groups'           => $this->repo->getGroups(),
            'accounts'         => $accounts,
            'selectedAccount'  => $selectedAccount,
            'initialCode'      => $initialCode,
            'searchQuery'      => $searchQuery,
            'selectedGroup'    => $selectedGroup,
            'frequentMistakes' => $this->repo->getFrequentMistakes(),
            'divhumasCases'    => $this->repo->getDivhumasCases(),
            'quizQuestions'    => $this->repo->getQuizQuestions(),
        ];

        return view('account_codes/index', $data);
    }

    public function detail(string $code)
    {
        $account = $this->repo->findByCode($code);

        if (!$account) {
            throw PageNotFoundException::forPageNotFound("Kode akun {$code} tidak ditemukan dalam Modul RKAKL.");
        }

        $data = [
            'title'            => "Kode Akun {$account['kode']} — {$account['uraian']}",
            'meta'             => $this->repo->getMeta(),
            'account'          => $account,
            'relatedAccounts'  => $this->repo->search('', substr($account['kode'], 0, 4)),
            'frequentMistakes' => array_values(array_filter(
                $this->repo->getFrequentMistakes(),
                fn($m) => str_contains($m['topik'], $account['kode']) || str_contains($m['seharusnya'], $account['kode'])
            )),
            'divhumasCases'    => array_values(array_filter(
                $this->repo->getDivhumasCases(),
                fn($c) => str_contains($c['akun_tepat'], $account['kode'])
            )),
        ];

        return view('account_codes/detail', $data);
    }

    public function apiSearch()
    {
        $q = (string)$this->request->getGet('q');
        $group = (string)$this->request->getGet('group');

        $results = $this->repo->search($q, $group ?: null);

        return $this->response->setJSON([
            'success' => true,
            'count'   => count($results),
            'data'    => $results
        ]);
    }
}
