Vagrant.configure("2") do |config|

  config.vm.box = "ubuntu/xenial64"
  config.vm.network "private_network", ip: "192.168.1.11"
  config.vm.synced_folder ".", "/var/www/default/htdocs/sdk/"
  config.vm.provision :ansible do |ansible|
    ansible.playbook = "provision/sdk.yml"
  end

end
